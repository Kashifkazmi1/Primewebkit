<?php

declare(strict_types=1);

namespace App\Repositories;

final class TeamInvitationRepository extends BaseRepository
{
    protected string $table = 'team_invitations';
    protected bool $usesSoftDeletes = false;

    public function findValidByTokenHash(string $tokenHash): ?array
    {
        return $this->query()
            ->where('token_hash', '=', $tokenHash)
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->whereRaw('expires_at > NOW()')
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingForTeam(int $teamId): array
    {
        return $this->query()
            ->where('team_id', '=', $teamId)
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public function findPendingForEmail(int $teamId, string $email): ?array
    {
        return $this->query()
            ->where('team_id', '=', $teamId)
            ->where('email', '=', $email)
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->first();
    }

    public function markAccepted(int $id): void
    {
        $this->update($id, ['accepted_at' => now_utc()->format('Y-m-d H:i:s')]);
    }

    public function revoke(int $id): void
    {
        $this->update($id, ['revoked_at' => now_utc()->format('Y-m-d H:i:s')]);
    }
}
