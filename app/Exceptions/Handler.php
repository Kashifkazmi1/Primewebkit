<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Core\Http\JsonResponse;
use App\Core\Http\Response;
use App\Core\Logging\LoggerFactory;
use Throwable;

/**
 * Single point of truth for turning any Throwable raised during a
 * request into a well-formed JSON response, while making sure every
 * error is logged with enough context to be debuggable.
 */
final class Handler
{
    public function handle(Throwable $exception): Response
    {
        $this->report($exception);

        if ($exception instanceof ApiException) {
            $response = JsonResponse::fromException($exception);

            if ($exception instanceof RateLimitException) {
                $response->setHeader('Retry-After', (string) $exception->getRetryAfterSeconds());
            }

            return $response;
        }

        $debug = (bool) config('app.debug', false);

        return JsonResponse::error(
            $debug ? $exception->getMessage() : 'An unexpected error occurred. Please try again later.',
            500,
            $debug ? ['exception' => [$exception::class], 'trace' => explode("\n", $exception->getTraceAsString())] : []
        );
    }

    private function report(Throwable $exception): void
    {
        $context = [
            'exception' => $exception::class,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        $logger = LoggerFactory::channel($exception instanceof ApiException && $exception->getStatusCode() < 500 ? 'api' : 'system');

        if (!$exception instanceof ApiException || $exception->getStatusCode() >= 500) {
            $logger->error($exception->getMessage(), $context);
        } else {
            $logger->info($exception->getMessage(), $context);
        }
    }
}
