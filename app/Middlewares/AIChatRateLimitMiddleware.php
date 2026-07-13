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
 * A stricter, dedicated rate limit for endpoints that trigger a paid
 * (eventually) AI provider call, keyed by bot + visitor fingerprint
 * (falling back to IP if no fingerprint is present yet) rather than
 * the general per-route API throttle — a single chatty visitor
 * shouldn't be able to run up an unbounded AI bill.
 */
final class AIChatRateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly RateLimiter $rateLimiter)
    {
    }

    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        $maxAttempts = (int) config('ai.chat_rate_limit.max_attempts', 20);
        $decaySeconds = (int) config('ai.chat_rate_limit.decay_seconds', 60);

        $botUuid = (string) $request->routeParam('botUuid', 'unknown');
        $identity = (string) ($request->input('fingerprint') ?? $request->ip());
        $key = "ai_chat:{$botUuid}:{$identity}";

        if (!$this->rateLimiter->attempt($key, $maxAttempts, $decaySeconds)) {
            throw new RateLimitException(
                'You are sending messages too quickly. Please wait a moment before trying again.',
                $this->rateLimiter->availableInSeconds($key)
            );
        }

        return $next($request);
    }
}
