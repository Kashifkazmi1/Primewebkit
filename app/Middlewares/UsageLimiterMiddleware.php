<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Contracts\MiddlewareInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\NotFoundException;
use App\Exceptions\UsageLimitExceededException;
use App\Models\User;
use App\Repositories\BotRepository;
use App\Services\PlanLimitService;
use Closure;

/**
 * Enforces plan limits before an action that would consume quota.
 * Usage: `UsageLimiterMiddleware::class . ':bots'` (numeric limit) or
 * `':streaming'` (feature flag) as a route middleware parameter.
 *
 * Resolves the account owner whose plan governs the limit two ways:
 *  - Authenticated dashboard routes: the logged-in user (`user` request attribute).
 *  - Public widget routes (e.g. sending a message): the bot's owner, via the `{botUuid}` route parameter — the visitor has no plan of their own; the bot owner's plan is what's being consumed.
 */
final class UsageLimiterMiddleware implements MiddlewareInterface
{
    private const FEATURE_FLAGS = ['api_access', 'analytics', 'white_label', 'custom_domain', 'priority_support', 'streaming'];

    private const METRIC_LABELS = [
        'bots' => 'bots',
        'messages' => 'messages this month',
        'knowledge_mb' => 'knowledge base storage',
        'storage_mb' => 'file storage',
        'team_members' => 'team members',
    ];

    public function __construct(
        private readonly PlanLimitService $planLimits,
        private readonly BotRepository $bots,
    ) {
    }

    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        $metric = $params[0] ?? null;

        if ($metric === null) {
            return $next($request);
        }

        $userId = $this->resolveAccountOwnerId($request);

        if ($userId === null) {
            return $next($request);
        }

        if (in_array($metric, self::FEATURE_FLAGS, true)) {
            if (!$this->planLimits->hasFeature($userId, $metric)) {
                throw new UsageLimitExceededException(
                    "Your current plan does not include this feature. Please upgrade to access it.",
                    $metric
                );
            }

            return $next($request);
        }

        if ($this->planLimits->hasReachedLimit($userId, $metric)) {
            $label = self::METRIC_LABELS[$metric] ?? $metric;

            throw new UsageLimitExceededException(
                "You've reached your plan's limit for {$label}. Please upgrade your plan to continue.",
                $metric
            );
        }

        return $next($request);
    }

    private function resolveAccountOwnerId(Request $request): ?int
    {
        /** @var User|null $user */
        $user = $request->getAttribute('user');

        if ($user !== null) {
            return $user->id;
        }

        $botUuid = $request->routeParam('botUuid');

        if (is_string($botUuid)) {
            $bot = $this->bots->findByUuid($botUuid);

            if ($bot === null) {
                throw new NotFoundException('Bot not found.');
            }

            return (int) $bot['user_id'];
        }

        return null;
    }
}
