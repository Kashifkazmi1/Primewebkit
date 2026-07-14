<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Contracts\MiddlewareInterface;
use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use Closure;

/**
 * Applies CORS headers based on config/cors.php and short-circuits
 * preflight OPTIONS requests with a 204.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        if ($request->method() === 'OPTIONS') {
            $response = JsonResponse::success(null, 'Preflight OK', 204);
        } else {
            $response = $next($request);
        }

        self::applyHeaders($request, $response);

        return $response;
    }

    /**
     * Attaches CORS headers to a response for the given request.
     *
     * Called both from handle() above for the normal pipeline, and
     * directly from Application::handle()'s exception-handling branch.
     * A thrown exception (AuthenticationException from a bad password
     * or an expired/invalid token, a ValidationException, literally
     * anything raised via `throw`) never reaches the `$next($request)`
     * call above — it propagates straight past this middleware to the
     * top-level catch in Application::handle(). Without applying these
     * same headers there too, every error response would come back
     * with no CORS headers at all, which browsers report as "blocked
     * by CORS policy" — masking the real status code and message
     * behind what looks like a origin-configuration problem.
     */
    public static function applyHeaders(Request $request, Response $response): void
    {
        $allowedOrigins = (array) config('cors.allowed_origins', []);
        $origin = (string) $request->header('origin', '');

        // The public widget API must be embeddable on ANY customer
        // website — that is its entire purpose. These endpoints carry
        // no cookies or credentials (visitors are identified by a
        // client-generated session id), and per-bot domain restriction
        // is enforced separately by WidgetOriginMiddleware. So, like
        // every commercial chat widget, they answer with a wildcard
        // CORS origin regardless of the configured allow-list.
        $apiPrefix = '/api/' . config('app.api_version', 'v1');
        $isPublicWidgetRoute = str_starts_with($request->uri(), $apiPrefix . '/widget/');

        $matchedExplicitOrigin = in_array($origin, $allowedOrigins, true);
        $matchedWildcard = in_array('*', $allowedOrigins, true) || $isPublicWidgetRoute;
        $originAllowed = $matchedExplicitOrigin || $matchedWildcard;

        if ($originAllowed && $origin !== '') {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Vary', 'Origin');
        }

        $response->setHeader('Access-Control-Allow-Methods', implode(', ', (array) config('cors.allowed_methods', [])));
        $response->setHeader('Access-Control-Allow-Headers', implode(', ', (array) config('cors.allowed_headers', [])));
        $response->setHeader('Access-Control-Max-Age', (string) config('cors.max_age', 86400));

        // Never combine reflected-wildcard origins with credentials —
        // "any origin + credentials" is the textbook CORS
        // misconfiguration that turns Bearer-token auth (which doesn't
        // rely on ambient browser credentials, so is otherwise immune
        // to CSRF-style abuse) into something a malicious site could
        // ride on if a browser ever mis-enforced the combination, or a
        // non-browser client ignored it entirely. Only send this
        // header when the origin was explicitly allow-listed.
        if ($matchedExplicitOrigin && !$isPublicWidgetRoute && (bool) config('cors.allow_credentials', false)) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }
    }
}
