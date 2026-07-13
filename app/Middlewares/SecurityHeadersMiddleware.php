<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Contracts\MiddlewareInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;
use Closure;

/**
 * Attaches the standard secure-headers set (X-Content-Type-Options,
 * X-Frame-Options, HSTS, CSP, etc.) defined in config/security.php to
 * every outgoing response.
 */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        $response = $next($request);

        foreach ((array) config('security.headers', []) as $name => $value) {
            $response->setHeader($name, (string) $value);
        }

        $hsts = (array) config('security.hsts', []);

        if (($hsts['enabled'] ?? false) && $request->isSecure()) {
            $response->setHeader(
                'Strict-Transport-Security',
                'max-age=' . (int) ($hsts['max_age'] ?? 31536000) . '; includeSubDomains; preload'
            );
        }

        return $response;
    }
}
