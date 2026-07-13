<?php

declare(strict_types=1);

namespace App\Repositories;

final class TeamMemberRepository extends BaseRepository
{
    protected string $table = 'team_members';
    protected bool $usesSoftDeletes = false;

    public function findMembership(int $teamId, int $userId): ?array
    {
        return $this->query()->where('team_id', '=', $teamId)->where('user_id', '=', $userId)->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function membersOf(int $teamId): array
    {
        return \App\Core\Database\QueryBuilder::table('team_members')
            ->withoutSoftDeletes()
            ->select(['team_members.*', 'users.name', 'users.email', 'users.avatar_path'])
            ->join('users', 'users.id', '=', 'team_members.user_id')
            ->where('team_members.team_id', '=', $teamId)
            ->orderBy('team_members.joined_at', 'ASC')
            ->get();
    }

    public function countFor(int $teamId): int
    {
        return $this->query()->where('team_id', '=', $teamId)->count();
    }

    /**
     * Total member count across every team a user owns, in one query
     * (avoids an N+1 of one countFor() call per owned team).
     */
    public function countMembersAcrossOwnedTeams(int $userId): int
    {
        $row = \App\Core\Database\QueryBuilder::table('team_members')
            ->withoutSoftDeletes()
            ->select(['COUNT(*) AS total'])
            ->join('teams', 'teams.id', '=', 'team_members.team_id')
            ->where('teams.owner_id', '=', $userId)
            ->first();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function teamsForUser(int $userId): array
    {
        return \App\Core\Database\QueryBuilder::table('team_members')
            ->withoutSoftDeletes()
            ->select(['team_members.*', 'teams.uuid AS team_uuid', 'teams.name AS team_name'])
            ->join('teams', 'teams.id', '=', 'team_members.team_id')
            ->where('team_members.user_id', '=', $userId)
            ->get();
    }

    public function removeMember(int $teamId, int $userId): void
    {
        $this->query()->where('team_id', '=', $teamId)->where('user_id', '=', $userId)->delete();
    }

    public function updateRole(int $teamId, int $userId, string $role): void
    {
        $this->query()->where('team_id', '=', $teamId)->where('user_id', '=', $userId)->update(['role' => $role]);
    }
}
