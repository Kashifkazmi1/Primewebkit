<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Contracts\MiddlewareInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\AuthorizationException;
use App\Exceptions\NotFoundException;
use App\Repositories\WidgetRepository;
use Closure;

/**
 * Restricts public widget endpoints (embed config, send-message) to
 * the domains an account owner has explicitly allow-listed for that
 * bot's widget. If no domains are configured, the widget is
 * embeddable anywhere (matches Chatbase's default behaviour).
 *
 * Expects a `{botUuid}` route parameter.
 */
final class WidgetOriginMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly WidgetRepository $widgets)
    {
    }

    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        $botUuid = $request->routeParam('botUuid');

        if (!is_string($botUuid)) {
            throw new NotFoundException('Bot not found.');
        }

        $widgetRow = $this->widgets->findByBotUuid($botUuid);

        if ($widgetRow === null) {
            throw new NotFoundException('Widget not found.');
        }

        $allowedDomains = !empty($widgetRow['allowed_domains'])
            ? (json_decode((string) $widgetRow['allowed_domains'], true) ?: [])
            : [];

        if (empty($allowedDomains)) {
            return $next($request);
        }

        $origin = (string) ($request->header('origin') ?? $request->header('referer') ?? '');
        $host = parse_url($origin, PHP_URL_HOST) ?: $origin;

        foreach ($allowedDomains as $allowed) {
            if ($allowed === '*' || strcasecmp((string) $allowed, (string) $host) === 0) {
                return $next($request);
            }
        }

        throw new AuthorizationException('This domain is not authorized to embed this widget.');
    }
}
