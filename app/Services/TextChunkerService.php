<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Splits long text into overlapping chunks suitable for embedding +
 * retrieval (Phase 4). Splits on paragraph/sentence boundaries where
 * possible rather than mid-word, and keeps a small overlap between
 * consecutive chunks so context isn't lost at chunk boundaries.
 */
final class TextChunkerService
{
    public function chunk(string $text, int $chunkSize = 1000, int $overlap = 150): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $paragraphs = preg_split('/\n{2,}/', $text) ?: [$text];
        $chunks = [];
        $current = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                continue;
            }

            if (mb_strlen($current) + mb_strlen($paragraph) + 2 <= $chunkSize) {
                $current = $current === '' ? $paragraph : $current . "\n\n" . $paragraph;

                continue;
            }

            if ($current !== '') {
                $chunks[] = $current;
                $current = mb_substr($current, max(0, mb_strlen($current) - $overlap));
            }

            // A single paragraph longer than chunkSize must itself be split.
            if (mb_strlen($paragraph) > $chunkSize) {
                foreach ($this->splitLongParagraph($paragraph, $chunkSize, $overlap) as $piece) {
                    $chunks[] = $piece;
                }
                $current = '';

                continue;
            }

            $current = $current === '' ? $paragraph : $current . "\n\n" . $paragraph;
        }

        if (trim($current) !== '') {
            $chunks[] = $current;
        }

        return array_values(array_filter(array_map('trim', $chunks), fn (string $c) => $c !== ''));
    }

    /**
     * @return list<string>
     */
    private function splitLongParagraph(string $paragraph, int $chunkSize, int $overlap): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $paragraph) ?: [$paragraph];
        $pieces = [];
        $current = '';

        foreach ($sentences as $sentence) {
            if (mb_strlen($current) + mb_strlen($sentence) + 1 <= $chunkSize) {
                $current = $current === '' ? $sentence : $current . ' ' . $sentence;

                continue;
            }

            if ($current !== '') {
                $pieces[] = $current;
                $current = mb_substr($current, max(0, mb_strlen($current) - $overlap));
            }

            // A single sentence longer than chunkSize gets hard-split.
            if (mb_strlen($sentence) > $chunkSize) {
                foreach (str_split($sentence, $chunkSize) as $hardPiece) {
                    $pieces[] = $hardPiece;
                }
                $current = '';

                continue;
            }

            $current = $current === '' ? $sentence : $current . ' ' . $sentence;
        }

        if (trim($current) !== '') {
            $pieces[] = $current;
        }

        return $pieces;
    }
}
