<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Exceptions\ExternalServiceException;

/**
 * A small cURL-based HTTP client with automatic retry (exponential
 * backoff + jitter) for transient failures, used by every outbound
 * AI provider integration. Kept dependency-free (no Guzzle) to match
 * the platform's lightweight-core philosophy.
 */
final class ExternalHttpClient
{
    public function __construct(
        private readonly int $timeoutSeconds = 30,
        private readonly int $connectTimeoutSeconds = 5,
        private readonly int $maxAttempts = 3,
        private readonly int $baseDelayMs = 300,
    ) {
    }

    /**
     * @param array<string, string> $headers
     * @return array{status: int, body: string, headers: array<string, string>}
     */
    public function request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxAttempts) {
            $attempt++;

            try {
                return $this->doRequest($method, $url, $headers, $body);
            } catch (ExternalServiceException $e) {
                $lastException = $e;

                if (!$this->isRetryable($e) || $attempt >= $this->maxAttempts) {
                    throw $e;
                }

                $this->sleepWithBackoff($attempt);
            }
        }

        throw $lastException ?? new ExternalServiceException('HttpClient', 'Request failed after retries.');
    }

    /**
     * Streams the response body chunk-by-chunk to $onChunk as it
     * arrives (used for AI provider streaming endpoints). Does not
     * retry mid-stream — a partially-delivered stream cannot be
     * safely retried without the caller re-processing what it already
     * emitted to the client.
     *
     * @param array<string, string> $headers
     * @param callable(string): (bool|void) $onChunk Return false to abort the transfer early.
     */
    public function stream(string $method, string $url, array $headers, string $body, callable $onChunk): int
    {
        $ch = curl_init($url);
        $headerLines = $this->formatHeaders($headers);

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeoutSeconds,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_WRITEFUNCTION => function ($handle, string $chunk) use ($onChunk): int {
                $result = $onChunk($chunk);

                return $result === false ? 0 : strlen($chunk);
            },
        ]);

        curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        // CURLE_WRITE_ERROR (23) is expected when $onChunk deliberately
        // returned false to abort — not a real transport failure.
        if ($errno !== 0 && $errno !== 23) {
            throw new ExternalServiceException('HttpClient', "Stream request failed: {$error}");
        }

        return $statusCode;
    }

    /**
     * @param array<string, string> $headers
     * @return array{status: int, body: string, headers: array<string, string>}
     */
    private function doRequest(string $method, string $url, array $headers, ?string $body): array
    {
        $ch = curl_init($url);
        $responseHeaders = [];

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeoutSeconds,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HEADERFUNCTION => function ($handle, string $line) use (&$responseHeaders): int {
                $parts = explode(':', $line, 2);

                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return strlen($line);
            },
        ]);

        $responseBody = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno !== 0) {
            $isTimeout = in_array($errno, [CURLE_OPERATION_TIMEDOUT, CURLE_COULDNT_CONNECT], true);

            throw new ExternalServiceException(
                'HttpClient',
                $isTimeout ? "Request timed out: {$error}" : "Request failed: {$error}"
            );
        }

        if ($statusCode >= 500 || $statusCode === 429) {
            throw new ExternalServiceException(
                'HttpClient',
                "Upstream returned HTTP {$statusCode}: " . mb_substr((string) $responseBody, 0, 500)
            );
        }

        return [
            'status' => $statusCode,
            'body' => (string) $responseBody,
            'headers' => $responseHeaders,
        ];
    }

    private function isRetryable(ExternalServiceException $e): bool
    {
        // Our own doRequest() only throws ExternalServiceException for
        // timeouts, connection failures, 5xx, and 429 — all of which
        // are safe to retry for idempotent AI requests. 4xx client
        // errors (bad request, auth failure) are never wrapped this
        // way from doRequest and are surfaced as decode/validation
        // failures by the caller instead, so anything reaching here is
        // retryable by construction.
        return true;
    }

    private function sleepWithBackoff(int $attempt): void
    {
        $delayMs = $this->baseDelayMs * (2 ** ($attempt - 1));
        $jitterMs = random_int(0, (int) ($delayMs * 0.2));
        usleep(($delayMs + $jitterMs) * 1000);
    }

    /**
     * @param array<string, string> $headers
     * @return list<string>
     */
    private function formatHeaders(array $headers): array
    {
        $lines = [];

        foreach ($headers as $name => $value) {
            $lines[] = "{$name}: {$value}";
        }

        return $lines;
    }
}
