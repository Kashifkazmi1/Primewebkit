<?php

declare(strict_types=1);

use App\Controllers\Admin\AdminDashboardController;
use App\Controllers\Admin\AdminLogController;
use App\Controllers\Admin\AdminPlanController;
use App\Controllers\Admin\AdminSettingsController;
use App\Controllers\Admin\AdminSubscriptionController;
use App\Controllers\Admin\AdminUserController;
use App\Controllers\Admin\AdminWebhookController;
use App\Controllers\Api\AIUsageController;
use App\Controllers\Api\AnalyticsController;
use App\Controllers\Api\ApiKeyController;
use App\Controllers\Api\BotController;
use App\Controllers\Api\ConversationController;
use App\Controllers\Api\HealthController;
use App\Controllers\Api\KnowledgeSourceController;
use App\Controllers\Api\LeadController;
use App\Controllers\Api\NotificationController;
use App\Controllers\Api\SubscriptionController;
use App\Controllers\Api\WebhookController;
use App\Controllers\Api\WhiteLabelController;
use App\Controllers\Api\WidgetController;
use App\Controllers\Auth\AuthController;
use App\Controllers\Public\WidgetController as PublicWidgetController;
use App\Controllers\Team\TeamController;
use App\Middlewares\AIChatRateLimitMiddleware;
use App\Middlewares\CorsMiddleware;
use App\Middlewares\JwtAuthMiddleware;
use App\Middlewares\PermissionMiddleware;
use App\Middlewares\RoleMiddleware;
use App\Middlewares\SecurityHeadersMiddleware;
use App\Middlewares\ThrottleMiddleware;
use App\Middlewares\UsageLimiterMiddleware;
use App\Middlewares\WidgetOriginMiddleware;

/** @var App\Core\Application $app */
$router = $app->router();

$apiVersion = (string) config('app.api_version', 'v1');

$router->group([
    'prefix' => "/api/{$apiVersion}",
    'middleware' => [
        CorsMiddleware::class,
        SecurityHeadersMiddleware::class,
        ThrottleMiddleware::class,
    ],
], function ($router) {
    // ---------------------------------------------------------------
    // Public / unauthenticated
    // ---------------------------------------------------------------
    $router->get('/health', [HealthController::class, 'index']);

    // ---------------------------------------------------------------
    // Auth routes (public)
    // ---------------------------------------------------------------
    $router->group(['prefix' => '/auth'], function ($router) {
        $router->post('/register', [AuthController::class, 'register']);
        $router->post('/login', [AuthController::class, 'login']);
        $router->post('/google', [AuthController::class, 'googleLogin']);
        $router->post('/refresh', [AuthController::class, 'refresh']);
        $router->post('/logout', [AuthController::class, 'logout']);
        $router->post('/forgot-password', [AuthController::class, 'forgotPassword']);
        $router->post('/reset-password', [AuthController::class, 'resetPassword']);
        $router->get('/verify-email/{token}', [AuthController::class, 'verifyEmail']);
        $router->post('/resend-verification', [AuthController::class, 'resendVerification']);
    });

    // ---------------------------------------------------------------
    // Auth routes (authenticated)
    // ---------------------------------------------------------------
    $router->group(['prefix' => '/auth', 'middleware' => [JwtAuthMiddleware::class]], function ($router) {
        $router->get('/me', [AuthController::class, 'me']);
        $router->put('/profile', [AuthController::class, 'updateProfile']);
        $router->post('/change-password', [AuthController::class, 'changePassword']);
        $router->post('/logout-all', [AuthController::class, 'logoutAllDevices']);
        $router->delete('/account', [AuthController::class, 'deleteAccount']);
    });

    // ---------------------------------------------------------------
    // Bots + nested resources (authenticated, owner-scoped)
    // ---------------------------------------------------------------
    $router->group(['prefix' => '/bots', 'middleware' => [JwtAuthMiddleware::class]], function ($router) {
        $router->get('', [BotController::class, 'index']);
        $router->post('', [BotController::class, 'store'])->withMiddleware([UsageLimiterMiddleware::class . ':bots']);
        $router->get('/{uuid}', [BotController::class, 'show']);
        $router->put('/{uuid}', [BotController::class, 'update']);
        $router->delete('/{uuid}', [BotController::class, 'destroy']);
        $router->post('/{uuid}/reembed', [BotController::class, 'reembed']);

        $router->get('/{uuid}/knowledge-sources', [KnowledgeSourceController::class, 'index']);
        $router->post('/{uuid}/knowledge-sources/text', [KnowledgeSourceController::class, 'addText'])
            ->withMiddleware([UsageLimiterMiddleware::class . ':knowledge_mb']);
        $router->post('/{uuid}/knowledge-sources/qa', [KnowledgeSourceController::class, 'addQa'])
            ->withMiddleware([UsageLimiterMiddleware::class . ':knowledge_mb']);
        $router->post('/{uuid}/knowledge-sources/website', [KnowledgeSourceController::class, 'addWebsite'])
            ->withMiddleware([UsageLimiterMiddleware::class . ':knowledge_mb']);
        $router->post('/{uuid}/knowledge-sources/document', [KnowledgeSourceController::class, 'addDocument'])
            ->withMiddleware([UsageLimiterMiddleware::class . ':knowledge_mb', UsageLimiterMiddleware::class . ':storage_mb']);
        $router->delete('/{uuid}/knowledge-sources/{sourceUuid}', [KnowledgeSourceController::class, 'destroy']);

        $router->get('/{uuid}/widget', [WidgetController::class, 'show']);
        $router->put('/{uuid}/widget', [WidgetController::class, 'update']);
        $router->get('/{uuid}/widget/embed-script', [WidgetController::class, 'embedScript']);

        $router->get('/{uuid}/conversations', [ConversationController::class, 'index']);
        $router->get('/{uuid}/conversations/{conversationUuid}', [ConversationController::class, 'show']);
        $router->post('/{uuid}/conversations/{conversationUuid}/close', [ConversationController::class, 'close']);
        $router->get('/{uuid}/conversations/{conversationUuid}/export', [ConversationController::class, 'export']);

        $router->get('/{uuid}/leads', [LeadController::class, 'index']);

        $router->get('/{uuid}/usage', [AIUsageController::class, 'index']);
        $router->get('/{uuid}/usage/summary', [AIUsageController::class, 'summary']);

        $router->get('/{uuid}/analytics', [AnalyticsController::class, 'forBot'])
            ->withMiddleware([UsageLimiterMiddleware::class . ':analytics']);
    });

    // ---------------------------------------------------------------
    // Subscriptions, plans, invoices (authenticated)
    // ---------------------------------------------------------------
    $router->group(['prefix' => '/subscriptions', 'middleware' => [JwtAuthMiddleware::class]], function ($router) {
        $router->get('/plans', [SubscriptionController::class, 'plans']);
        $router->get('/current', [SubscriptionController::class, 'current']);
        $router->get('/history', [SubscriptionController::class, 'history']);
        $router->post('', [SubscriptionController::class, 'subscribe']);
        $router->post('/{uuid}/cancel', [SubscriptionController::class, 'cancel']);
        $router->get('/invoices', [SubscriptionController::class, 'invoices']);
    });

    // ---------------------------------------------------------------
    // Teams (authenticated)
    // ---------------------------------------------------------------
    $router->group(['prefix' => '/teams', 'middleware' => [JwtAuthMiddleware::class]], function ($router) {
        $router->get('', [TeamController::class, 'index']);
        $router->post('', [TeamController::class, 'store']);
        $router->get('/{uuid}', [TeamController::class, 'show']);
        $router->get('/{uuid}/members', [TeamController::class, 'members']);
        $router->post('/{uuid}/invite', [TeamController::class, 'invite'])
            ->withMiddleware([UsageLimiterMiddleware::class . ':team_members']);
        $router->post('/invitations/{token}/accept', [TeamController::class, 'acceptInvitation']);
        $router->delete('/{uuid}/members/{targetUserId}', [TeamController::class, 'removeMember']);
        $router->put('/{uuid}/members/{targetUserId}/role', [TeamController::class, 'updateMemberRole']);
    });

    // ---------------------------------------------------------------
    // Outgoing webhooks (authenticated, account owner manages their own)
    // ---------------------------------------------------------------
    $router->group(['prefix' => '/webhooks', 'middleware' => [JwtAuthMiddleware::class]], function ($router) {
        $router->get('/events', [WebhookController::class, 'events']);
        $router->get('', [WebhookController::class, 'index']);
        $router->post('', [WebhookController::class, 'store']);
        $router->delete('/{uuid}', [WebhookController::class, 'destroy']);
        $router->put('/{uuid}', [WebhookController::class, 'toggle']);
        $router->get('/{uuid}/logs', [WebhookController::class, 'logs']);
    });

    // ---------------------------------------------------------------
    // Notifications (authenticated)
    // ---------------------------------------------------------------
    $router->group(['prefix' => '/notifications', 'middleware' => [JwtAuthMiddleware::class]], function ($router) {
        $router->get('', [NotificationController::class, 'index']);
        $router->get('/unread-count', [NotificationController::class, 'unreadCount']);
        $router->post('/{uuid}/read', [NotificationController::class, 'markRead']);
        $router->post('/read-all', [NotificationController::class, 'markAllRead']);
    });

    // ---------------------------------------------------------------
    // White-label branding (authenticated, gated by plan feature)
    // ---------------------------------------------------------------
    $router->group(['prefix' => '/white-label', 'middleware' => [JwtAuthMiddleware::class]], function ($router) {
        $router->get('', [WhiteLabelController::class, 'show']);
        $router->put('', [WhiteLabelController::class, 'update']);
    });

    // ---------------------------------------------------------------
    // Personal API keys (authenticated)
    // ---------------------------------------------------------------
    $router->group(['prefix' => '/api-keys', 'middleware' => [JwtAuthMiddleware::class]], function ($router) {
        $router->get('', [ApiKeyController::class, 'index']);
        $router->post('', [ApiKeyController::class, 'store']);
        $router->delete('/{uuid}', [ApiKeyController::class, 'destroy']);
        $router->post('/{uuid}/rotate', [ApiKeyController::class, 'rotate']);
    });

    // ---------------------------------------------------------------
    // Public widget endpoints (embedded on customer websites)
    // ---------------------------------------------------------------
    $router->group(['prefix' => '/widget', 'middleware' => [WidgetOriginMiddleware::class]], function ($router) {
        $router->get('/{botUuid}/config', [PublicWidgetController::class, 'config']);
        $router->post('/{botUuid}/leads', [PublicWidgetController::class, 'captureLead']);
        $router->post('/{botUuid}/conversations/{conversationUuid}/rate', [PublicWidgetController::class, 'rateConversation']);

        // AI-generating endpoints get an additional, stricter,
        // cost-aware rate limit on top of the general API throttle,
        // plus plan usage-limit enforcement (messages/streaming).
        $router->group(['middleware' => [AIChatRateLimitMiddleware::class]], function ($router) {
            $router->post('/{botUuid}/messages', [PublicWidgetController::class, 'sendMessage'])
                ->withMiddleware([UsageLimiterMiddleware::class . ':messages']);
            $router->post('/{botUuid}/messages/stream', [PublicWidgetController::class, 'sendMessageStream'])
                ->withMiddleware([UsageLimiterMiddleware::class . ':messages', UsageLimiterMiddleware::class . ':streaming']);
            $router->post('/{botUuid}/conversations/{conversationUuid}/regenerate', [PublicWidgetController::class, 'regenerate']);
            $router->post('/{botUuid}/conversations/{conversationUuid}/stop', [PublicWidgetController::class, 'stopGeneration']);
            $router->get('/{botUuid}/conversations/{conversationUuid}/suggested-questions', [PublicWidgetController::class, 'suggestedQuestions']);
        });
    });

    // ---------------------------------------------------------------
    // Admin routes (super-admin / admin only, plus fine-grained
    // permission checks for the most sensitive actions)
    // ---------------------------------------------------------------
    $router->group([
        'prefix' => '/admin',
        'middleware' => [JwtAuthMiddleware::class, RoleMiddleware::class . ':super-admin,admin'],
    ], function ($router) {
        $router->get('/dashboard', [AdminDashboardController::class, 'overview']);

        $router->get('/users', [AdminUserController::class, 'index'])->withMiddleware([PermissionMiddleware::class . ':users.view']);
        $router->post('/users/{uuid}/suspend', [AdminUserController::class, 'suspend'])->withMiddleware([PermissionMiddleware::class . ':users.suspend']);
        $router->post('/users/{uuid}/activate', [AdminUserController::class, 'activate'])->withMiddleware([PermissionMiddleware::class . ':users.suspend']);
        $router->delete('/users/{uuid}', [AdminUserController::class, 'destroy'])->withMiddleware([PermissionMiddleware::class . ':users.delete']);
        $router->post('/users/{uuid}/reset-password', [AdminUserController::class, 'resetPassword'])->withMiddleware([PermissionMiddleware::class . ':users.update']);
        $router->post('/users/{uuid}/force-logout', [AdminUserController::class, 'forceLogout'])->withMiddleware([PermissionMiddleware::class . ':users.update']);
        $router->get('/users/{uuid}/activity', [AdminUserController::class, 'activity'])->withMiddleware([PermissionMiddleware::class . ':users.view']);
        $router->get('/users/{uuid}/login-history', [AdminUserController::class, 'loginHistory'])->withMiddleware([PermissionMiddleware::class . ':users.view']);
        $router->get('/users/{uuid}/api-usage', [AdminUserController::class, 'apiUsage'])->withMiddleware([PermissionMiddleware::class . ':users.view']);
        $router->get('/users/{uuid}/ai-usage', [AdminUserController::class, 'aiUsage'])->withMiddleware([PermissionMiddleware::class . ':users.view']);
        $router->get('/users/{uuid}/storage-usage', [AdminUserController::class, 'storageUsage'])->withMiddleware([PermissionMiddleware::class . ':users.view']);

        $router->get('/plans', [AdminPlanController::class, 'index'])->withMiddleware([PermissionMiddleware::class . ':plans.manage']);
        $router->post('/plans', [AdminPlanController::class, 'store'])->withMiddleware([PermissionMiddleware::class . ':plans.manage']);
        $router->get('/plans/{uuid}', [AdminPlanController::class, 'show'])->withMiddleware([PermissionMiddleware::class . ':plans.manage']);
        $router->put('/plans/{uuid}', [AdminPlanController::class, 'update'])->withMiddleware([PermissionMiddleware::class . ':plans.manage']);
        $router->delete('/plans/{uuid}', [AdminPlanController::class, 'destroy'])->withMiddleware([PermissionMiddleware::class . ':plans.manage']);

        $router->get('/invoices/pending', [AdminSubscriptionController::class, 'pendingInvoices'])->withMiddleware([PermissionMiddleware::class . ':subscriptions.manage']);
        $router->post('/invoices/{uuid}/mark-paid', [AdminSubscriptionController::class, 'markInvoicePaid'])->withMiddleware([PermissionMiddleware::class . ':subscriptions.manage']);
        $router->post('/invoices/{uuid}/void', [AdminSubscriptionController::class, 'voidInvoice'])->withMiddleware([PermissionMiddleware::class . ':subscriptions.manage']);

        $router->get('/coupons', [AdminSubscriptionController::class, 'listCoupons'])->withMiddleware([PermissionMiddleware::class . ':coupons.manage']);
        $router->post('/coupons', [AdminSubscriptionController::class, 'createCoupon'])->withMiddleware([PermissionMiddleware::class . ':coupons.manage']);
        $router->post('/coupons/{uuid}/deactivate', [AdminSubscriptionController::class, 'deactivateCoupon'])->withMiddleware([PermissionMiddleware::class . ':coupons.manage']);

        $router->get('/settings', [AdminSettingsController::class, 'index'])->withMiddleware([PermissionMiddleware::class . ':settings.manage']);
        $router->put('/settings', [AdminSettingsController::class, 'update'])->withMiddleware([PermissionMiddleware::class . ':settings.manage']);

        $router->get('/webhook-logs', [AdminWebhookController::class, 'index'])->withMiddleware([PermissionMiddleware::class . ':webhook-logs.view']);

        $router->get('/logs/errors', [AdminLogController::class, 'errorLogs'])->withMiddleware([PermissionMiddleware::class . ':audit-logs.view']);
        $router->get('/logs/security', [AdminLogController::class, 'securityLogs'])->withMiddleware([PermissionMiddleware::class . ':audit-logs.view']);
        $router->get('/logs/{channel}', [AdminLogController::class, 'channel'])->withMiddleware([PermissionMiddleware::class . ':audit-logs.view']);
    });
});
