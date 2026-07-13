<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Database\QueryBuilder;
use App\Core\Security\PasswordHasher;
use App\Exceptions\NotFoundException;
use App\Repositories\ActivityLogRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\SessionRepository;
use App\Repositories\UserRepository;
use App\Services\MailService;
use App\Services\PlanLimitService;
use App\Services\TokenService;

final class AdminUserService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly SessionRepository $sessions,
        private readonly ActivityLogRepository $activityLogs,
        private readonly AuditLogRepository $auditLogs,
        private readonly PasswordHasher $hasher,
        private readonly TokenService $tokens,
        private readonly MailService $mail,
        private readonly PlanLimitService $planLimits,
    ) {
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function search(?string $query, ?string $status, int $page, int $perPage): array
    {
        $builder = QueryBuilder::table('users')
            ->select(['users.*', 'roles.slug AS role_slug'])
            ->join('roles', 'roles.id', '=', 'users.role_id');

        if ($query !== null && trim($query) !== '') {
            $like = '%' . $query . '%';
            $builder->whereRaw('(users.name LIKE :name_like OR users.email LIKE :email_like)', [
                'name_like' => $like,
                'email_like' => $like,
            ]);
        }

        if ($status !== null) {
            $builder->where('users.status', '=', $status);
        }

        return $builder->orderBy('users.created_at', 'DESC')->paginate($page, $perPage);
    }

    public function suspend(string $uuid, int $adminUserId): void
    {
        $user = $this->findOrFail($uuid);
        $this->users->update((int) $user['id'], ['status' => 'suspended']);
        $this->sessions->revokeAllForUser((int) $user['id']);
        $this->auditLogs->record($adminUserId, 'admin.user_suspended', 'users', (int) $user['id'], '0.0.0.0', 'admin-panel');
    }

    public function activate(string $uuid, int $adminUserId): void
    {
        $user = $this->findOrFail($uuid);
        $this->users->update((int) $user['id'], ['status' => 'active', 'failed_login_attempts' => 0, 'locked_until' => null]);
        $this->auditLogs->record($adminUserId, 'admin.user_activated', 'users', (int) $user['id'], '0.0.0.0', 'admin-panel');
    }

    public function delete(string $uuid, int $adminUserId): void
    {
        $user = $this->findOrFail($uuid);
        $this->sessions->revokeAllForUser((int) $user['id']);
        $this->users->delete((int) $user['id']);
        $this->auditLogs->record($adminUserId, 'admin.user_deleted', 'users', (int) $user['id'], '0.0.0.0', 'admin-panel');
    }

    /**
     * Triggers a password reset email on the user's behalf — the
     * admin never sees or sets the new password directly.
     */
    public function triggerPasswordReset(string $uuid, int $adminUserId, string $resetUrlTemplate): void
    {
        $user = $this->findOrFail($uuid);

        $rawToken = $this->tokens->generate();
        $tokenHash = $this->tokens->hash($rawToken);

        QueryBuilder::table('password_resets')->withoutSoftDeletes()->insert([
            'email' => $user['email'],
            'token_hash' => $tokenHash,
            'expires_at' => now_utc()->modify('+60 minutes')->format('Y-m-d H:i:s'),
        ]);

        $resetUrl = str_replace('{token}', $rawToken, $resetUrlTemplate);
        $this->mail->sendPasswordReset($user['email'], $user['name'], $resetUrl);

        $this->auditLogs->record($adminUserId, 'admin.password_reset_triggered', 'users', (int) $user['id'], '0.0.0.0', 'admin-panel');
    }

    public function forceLogout(string $uuid, int $adminUserId): void
    {
        $user = $this->findOrFail($uuid);
        $this->sessions->revokeAllForUser((int) $user['id']);
        $this->auditLogs->record($adminUserId, 'admin.force_logout', 'users', (int) $user['id'], '0.0.0.0', 'admin-panel');
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function activity(string $uuid, int $page, int $perPage): array
    {
        $user = $this->findOrFail($uuid);

        return $this->activityLogs->forUser((int) $user['id'], $page, $perPage);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function loginHistory(string $uuid, int $limit = 50): array
    {
        $user = $this->findOrFail($uuid);

        return QueryBuilder::table('audit_logs')
            ->withoutSoftDeletes()
            ->where('user_id', '=', $user['id'])
            ->whereIn('action', ['auth.login_succeeded', 'auth.login_failed', 'auth.account_locked'])
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function apiUsage(string $uuid): array
    {
        $user = $this->findOrFail($uuid);

        return $this->planLimits->usageFor((int) $user['id']);
    }

    /**
     * @return array<string, mixed>
     */
    public function aiUsage(string $uuid): array
    {
        $user = $this->findOrFail($uuid);

        $row = QueryBuilder::table('ai_usage_logs')
            ->withoutSoftDeletes()
            ->select(['COUNT(*) AS total_requests', 'COALESCE(SUM(total_tokens), 0) AS total_tokens', 'COALESCE(SUM(estimated_cost), 0) AS total_cost'])
            ->join('bots', 'bots.id', '=', 'ai_usage_logs.bot_id')
            ->where('bots.user_id', '=', $user['id'])
            ->first();

        return [
            'total_requests' => (int) ($row['total_requests'] ?? 0),
            'total_tokens' => (int) ($row['total_tokens'] ?? 0),
            'total_cost' => round((float) ($row['total_cost'] ?? 0), 6),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function storageUsage(string $uuid): array
    {
        $user = $this->findOrFail($uuid);
        $usage = $this->planLimits->usageFor((int) $user['id']);

        return [
            'knowledge_mb' => $usage['knowledge_mb'],
            'storage_mb' => $usage['storage_mb'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function findOrFail(string $uuid): array
    {
        $user = $this->users->findByUuid($uuid);

        if ($user === null) {
            throw new NotFoundException('User not found.');
        }

        return $user;
    }
}
