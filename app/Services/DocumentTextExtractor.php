<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ExternalServiceException;
use RuntimeException;
use ZipArchive;

/**
 * Extracts plain text from uploaded knowledge-base files.
 *
 * Supported natively, without any external binary/library:
 *  - text/plain, text/markdown, text/csv — read directly.
 *  - .docx — a DOCX is a ZIP archive; we read word/document.xml and
 *    strip tags. Genuinely extracts real text, not a stub.
 *  - .pdf — best-effort extraction of text drawn via `Tj`/`TJ`
 *    operators in uncompressed and Flate-compressed content streams.
 *    This covers the large majority of text-based PDFs (exported from
 *    Word, Google Docs, etc.) but will not OCR scanned/image-only
 *    PDFs — that would require a dedicated OCR engine, which is
 *    outside what's reasonable to run on Hostinger shared hosting.
 */
final class DocumentTextExtractor
{
    public function extract(string $filePath, string $mimeType): string
    {
        return match (true) {
            str_contains($mimeType, 'pdf') => $this->extractPdf($filePath),
            str_contains($mimeType, 'wordprocessingml') => $this->extractDocx($filePath),
            default => $this->extractPlainText($filePath),
        };
    }

    private function extractPlainText(string $filePath): string
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new RuntimeException("Unable to read file: {$filePath}");
        }

        return $this->normalize($content);
    }

    private function extractDocx(string $filePath): string
    {
        $zip = new ZipArchive();

        if ($zip->open($filePath) !== true) {
            throw new ExternalServiceException('DocumentTextExtractor', 'Unable to open the .docx file as a ZIP archive.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new ExternalServiceException('DocumentTextExtractor', 'The .docx file does not contain word/document.xml.');
        }

        // Convert paragraph/break tags to newlines before stripping the
        // rest, so paragraphs don't run together into one long line.
        $xml = preg_replace('/<\/w:p>/', "\n", $xml) ?? $xml;
        $xml = preg_replace('/<w:br\s*\/?>/', "\n", $xml) ?? $xml;
        $text = strip_tags($xml);

        return $this->normalize(html_entity_decode($text, ENT_QUOTES | ENT_XML1));
    }

    private function extractPdf(string $filePath): string
    {
        $raw = file_get_contents($filePath);

        if ($raw === false) {
            throw new RuntimeException("Unable to read file: {$filePath}");
        }

        $text = '';

        // Find each stream ... endstream block, inflate if compressed,
        // then pull text out of Tj/TJ show-text operators.
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $streamMatches);

        foreach ($streamMatches[1] as $stream) {
            $decoded = @gzuncompress($stream);

            if ($decoded === false) {
                $decoded = @zlib_decode($stream);
            }

            $content = $decoded !== false ? $decoded : $stream;
            $text .= $this->extractTextOperators($content) . "\n";
        }

        $normalized = $this->normalize($text);

        if ($normalized === '') {
            throw new ExternalServiceException(
                'DocumentTextExtractor',
                'No extractable text was found in this PDF. It may be a scanned/image-only document, which requires OCR (not supported).'
            );
        }

        return $normalized;
    }

    private function extractTextOperators(string $streamContent): string
    {
        $text = '';

        // (text) Tj   -> single string show
        preg_match_all('/\((?:[^()\\\\]|\\\\.)*\)\s*Tj/', $streamContent, $tjMatches);

        foreach ($tjMatches[0] as $match) {
            $inner = substr($match, 1, strrpos($match, ')') - 1);
            $text .= $this->unescapePdfString($inner) . ' ';
        }

        // [ (text) (text) ... ] TJ -> array show
        preg_match_all('/\[(.*?)\]\s*TJ/s', $streamContent, $tjArrayMatches);

        foreach ($tjArrayMatches[1] as $arrayContent) {
            preg_match_all('/\((?:[^()\\\\]|\\\\.)*\)/', $arrayContent, $stringMatches);

            foreach ($stringMatches[0] as $str) {
                $inner = substr($str, 1, -1);
                $text .= $this->unescapePdfString($inner);
            }

            $text .= ' ';
        }

        return $text;
    }

    private function unescapePdfString(string $value): string
    {
        $value = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $value);

        return preg_replace('/\\\\[0-9]{3}/', '', $value) ?? $value;
    }

    private function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
