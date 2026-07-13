<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Contracts\MiddlewareInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Security\RateLimiter;
use App\Exceptions\RateLimitException;
use Closure;

/**
 * General-purpose per-IP, per-route rate limiter. Route-specific,
 * stricter throttles (e.g. login) should use their own middleware
 * with tighter limits rather than relying solely on this one.
 */
final class ThrottleMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly RateLimiter $rateLimiter)
    {
    }

    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        if (!(bool) config('security.rate_limit.enabled', true)) {
            return $next($request);
        }

        $maxAttempts = (int) config('security.rate_limit.max_attempts', 60);
        $decaySeconds = (int) config('security.rate_limit.decay_seconds', 60);
        $key = 'throttle:' . $request->ip() . ':' . $request->uri();

        if (!$this->rateLimiter->attempt($key, $maxAttempts, $decaySeconds)) {
            throw new RateLimitException(
                'Too many requests. Please slow down and try again shortly.',
                $this->rateLimiter->availableInSeconds($key)
            );
        }

        $response = $next($request);

        $response->setHeader('X-RateLimit-Limit', (string) $maxAttempts);
        $response->setHeader('X-RateLimit-Remaining', (string) $this->rateLimiter->remaining($key, $maxAttempts));

        return $response;
    }
}
