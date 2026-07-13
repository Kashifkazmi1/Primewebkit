<?php

declare(strict_types=1);

namespace App\Core\Http;

use Closure;

/**
 * A response whose body is produced incrementally by a callback
 * (invoked from send()) rather than pre-built into a string — used
 * for Server-Sent Events streaming, where chunks must reach the
 * client as they're generated, not after the whole response is ready.
 */
final class StreamedResponse extends Response
{
    public function __construct(private readonly Closure $streamCallback, array $headers = [])
    {
        parent::__construct('', 200, array_merge([
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store',
            'Connection' => 'keep-alive',
            // Tells nginx (if used as a reverse proxy in front of
            // Apache/PHP-FPM) not to buffer the response, which would
            // otherwise defeat streaming entirely.
            'X-Accel-Buffering' => 'no',
        ], $headers));
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        // Disable every layer of output buffering so each flush()
        // inside the callback actually reaches the client immediately
        // rather than sitting in a PHP/webserver buffer.
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }

        ($this->streamCallback)();
    }

    /**
     * Writes one SSE frame. Call from within the stream callback.
     */
    public static function writeEvent(string $data, ?string $event = null): void
    {
        if ($event !== null) {
            echo "event: {$event}\n";
        }

        foreach (explode("\n", $data) as $line) {
            echo "data: {$line}\n";
        }

        echo "\n";
        flush();
    }
}
