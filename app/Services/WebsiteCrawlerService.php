<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ExternalServiceException;
use App\Exceptions\ValidationException;
use App\Core\Security\SsrfGuard;

/**
 * A small, same-domain website crawler. Fetches pages via cURL,
 * strips scripts/styles/nav/footer boilerplate, extracts visible
 * text, and discovers same-domain links to follow up to a page cap.
 *
 * Runs synchronously from the CLI cron script (bin/process-crawl-jobs.php)
 * rather than inline on an HTTP request, since shared hosting request
 * timeouts (typically 30-60s) are far too short to crawl multiple pages.
 */
final class WebsiteCrawlerService
{
    private const USER_AGENT = 'AI-Chatbot-SaaS-Crawler/1.0 (+https://yourdomain.com)';
    private const TIMEOUT_SECONDS = 15;
    private const MAX_REDIRECTS = 3;

    /**
     * @return array{pages: array<string, string>, discovered: list<string>}
     */
    public function crawl(string $startUrl, int $maxPages = 20): array
    {
        $host = parse_url($startUrl, PHP_URL_HOST);

        if ($host === null || $host === false) {
            throw new ExternalServiceException('WebsiteCrawler', "Invalid start URL: {$startUrl}");
        }

        $queue = [$startUrl];
        $visited = [];
        $pages = [];

        while (!empty($queue) && count($pages) < $maxPages) {
            $url = array_shift($queue);
            $normalized = $this->normalizeUrl($url);

            if (isset($visited[$normalized])) {
                continue;
            }

            $visited[$normalized] = true;

            $html = $this->fetch($url);

            if ($html === null) {
                continue;
            }

            $text = $this->extractVisibleText($html);

            if (trim($text) !== '') {
                $pages[$url] = $text;
            }

            if (count($pages) >= $maxPages) {
                break;
            }

            foreach ($this->extractLinks($html, $url, $host) as $link) {
                $normalizedLink = $this->normalizeUrl($link);

                if (!isset($visited[$normalizedLink]) && !in_array($link, $queue, true)) {
                    $queue[] = $link;
                }
            }
        }

        return [
            'pages' => $pages,
            'discovered' => array_keys($visited),
        ];
    }

    private function fetch(string $url): ?string
    {
        // Redirects are followed manually (not via CURLOPT_FOLLOWLOCATION)
        // so every hop is re-validated by SsrfGuard — otherwise a
        // malicious page could pass the initial check and then 302
        // redirect straight to an internal address, bypassing it entirely.
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            try {
                SsrfGuard::assertSafeUrl($url);
            } catch (ValidationException) {
                return null;
            }

            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_USERAGENT => self::USER_AGENT,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER => ['Accept: text/html'],
            ]);

            $body = curl_exec($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            $error = curl_error($ch);
            curl_close($ch);

            if ($body === false || $error !== '') {
                return null;
            }

            if (in_array($statusCode, [301, 302, 303, 307, 308], true)) {
                if (empty($redirectUrl) || !is_string($redirectUrl)) {
                    return null;
                }

                $url = $this->resolveUrl($redirectUrl, $url);

                continue;
            }

            if ($statusCode >= 400) {
                return null;
            }

            if ($contentType !== '' && !str_contains($contentType, 'text/html') && !str_contains($contentType, 'application/xhtml')) {
                return null;
            }

            return $body;
        }

        return null;
    }

    private function extractVisibleText(string $html): string
    {
        // Remove script/style/noscript blocks entirely (their text content
        // is never visible to a user and would pollute the knowledge base).
        $html = preg_replace('#<(script|style|noscript)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;

        // Convert common block-level boundaries to newlines before
        // stripping tags, so paragraphs remain separated.
        $html = preg_replace('#</(p|div|h[1-6]|li|br|tr)>#i', "\n", $html) ?? $html;

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @return list<string>
     */
    private function extractLinks(string $html, string $baseUrl, string $host): array
    {
        preg_match_all('/<a\s[^>]*href=["\']([^"\'#]+)["\']/i', $html, $matches);

        $links = [];

        foreach ($matches[1] as $href) {
            $href = trim($href);

            if ($href === '' || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) {
                continue;
            }

            $resolved = $this->resolveUrl($href, $baseUrl);
            $linkHost = parse_url($resolved, PHP_URL_HOST);

            if ($linkHost !== null && strcasecmp($linkHost, $host) === 0) {
                $links[] = $resolved;
            }
        }

        return array_values(array_unique($links));
    }

    private function resolveUrl(string $href, string $baseUrl): string
    {
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        $base = parse_url($baseUrl);
        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'] ?? '';

        if (str_starts_with($href, '//')) {
            return "{$scheme}:{$href}";
        }

        if (str_starts_with($href, '/')) {
            return "{$scheme}://{$host}{$href}";
        }

        $basePath = isset($base['path']) ? rtrim(dirname($base['path']), '/') : '';

        return "{$scheme}://{$host}{$basePath}/{$href}";
    }

    private function normalizeUrl(string $url): string
    {
        $parts = parse_url($url);
        $path = rtrim($parts['path'] ?? '/', '/') ?: '/';

        return strtolower(($parts['host'] ?? '') . $path);
    }
}
