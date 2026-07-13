<?php

declare(strict_types=1);

namespace App\Core\Security;

/**
 * Simple fixed-window rate limiter backed by flat files under
 * storage/Cache/Rate. Deliberately dependency-free (no Redis/Memcached)
 * so it works unmodified on constrained shared hosting.
 *
 * Not perfectly atomic under extreme concurrency, but uses flock() to
 * avoid lost updates under normal API traffic levels.
 */
final class RateLimiter
{
    private readonly string $storagePath;

    public function __construct(?string $storagePath = null)
    {
        $this->storagePath = $storagePath ?? storage_path('Cache/Rate');

        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0775, true);
        }
    }

    /**
     * Returns true if the given key has NOT exceeded $maxAttempts within
     * $decaySeconds, and records this attempt.
     */
    public function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $file = $this->pathFor($key);
        $handle = fopen($file, 'c+');

        if ($handle === false) {
            // Fail open rather than blocking all traffic if the filesystem
            // is temporarily unavailable.
            return true;
        }

        flock($handle, LOCK_EX);

        $raw = stream_get_contents($handle) ?: '';
        $state = json_decode($raw, true);
        $now = time();

        if (!is_array($state) || ($state['reset_at'] ?? 0) <= $now) {
            $state = ['count' => 0, 'reset_at' => $now + $decaySeconds];
        }

        $allowed = $state['count'] < $maxAttempts;

        if ($allowed) {
            $state['count']++;
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($state));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $allowed;
    }

    public function remaining(string $key, int $maxAttempts): int
    {
        $state = $this->readState($key);

        if ($state === null) {
            return $maxAttempts;
        }

        return max(0, $maxAttempts - (int) $state['count']);
    }

    public function availableInSeconds(string $key): int
    {
        $state = $this->readState($key);

        if ($state === null) {
            return 0;
        }

        return max(0, (int) $state['reset_at'] - time());
    }

    public function clear(string $key): void
    {
        $file = $this->pathFor($key);

        if (is_file($file)) {
            unlink($file);
        }
    }

    private function readState(string $key): ?array
    {
        $file = $this->pathFor($key);

        if (!is_file($file)) {
            return null;
        }

        $raw = file_get_contents($file);
        $state = $raw !== false ? json_decode($raw, true) : null;

        if (!is_array($state) || ($state['reset_at'] ?? 0) <= time()) {
            return null;
        }

        return $state;
    }

    private function pathFor(string $key): string
    {
        return $this->storagePath . '/' . hash('sha256', $key) . '.json';
    }
}
