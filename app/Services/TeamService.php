<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AuthorizationException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Team;
use App\Repositories\ActivityLogRepository;
use App\Repositories\TeamInvitationRepository;
use App\Repositories\TeamMemberRepository;
use App\Repositories\TeamRepository;
use App\Repositories\UserRepository;

/**
 * Team roles (owner/admin/editor/viewer) are distinct from platform
 * roles (super-admin/admin/user, see Phase 2) — a team role only
 * governs what a member can do within that specific team's
 * resources, and has no bearing on platform-level permissions.
 */
final class TeamService
{
    private const ROLE_RANK = ['viewer' => 1, 'editor' => 2, 'admin' => 3, 'owner' => 4];

    public function __construct(
        private readonly TeamRepository $teams,
        private readonly TeamMemberRepository $members,
        private readonly TeamInvitationRepository $invitations,
        private readonly UserRepository $users,
        private readonly TokenService $tokens,
        private readonly MailService $mail,
        private readonly ActivityLogRepository $activityLogs,
    ) {
    }

    /**
     * Every team the user belongs to, with their role in each.
     * Backs the dashboard's Team page, which needs to discover the
     * user's team uuid(s) after a fresh page load.
     *
     * @return list<array{id: string, name: string, role: string, joined_at: ?string}>
     */
    public function myTeams(int $userId): array
    {
        $rows = $this->members->teamsForUser($userId);

        return array_map(static fn (array $row) => [
            'id' => (string) $row['team_uuid'],
            'name' => (string) $row['team_name'],
            'role' => (string) $row['role'],
            'joined_at' => $row['joined_at'] ?? null,
        ], $rows);
    }

    public function create(int $ownerId, string $name): Team
    {
        $id = (int) $this->teams->create([
            'uuid' => str_uuid4(),
            'owner_id' => $ownerId,
            'name' => $name,
        ]);

        $this->members->create([
            'team_id' => $id,
            'user_id' => $ownerId,
            'role' => 'owner',
            'joined_at' => now_utc()->format('Y-m-d H:i:s'),
        ]);

        return Team::fromArray($this->teams->find($id));
    }

    public function getForMember(string $uuid, int $userId): Team
    {
        $row = $this->teams->findByUuid($uuid);

        if ($row === null) {
            throw new NotFoundException('Team not found.');
        }

        $this->requireMembership((int) $row['id'], $userId);

        return Team::fromArray($row);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function membersOf(string $teamUuid, int $requestingUserId): array
    {
        $team = $this->getForMember($teamUuid, $requestingUserId);

        return $this->members->membersOf($team->id);
    }

    public function invite(string $teamUuid, int $invitedByUserId, string $email, string $role): array
    {
        $team = $this->getForMember($teamUuid, $invitedByUserId);
        $this->requireRole($team->id, $invitedByUserId, 'admin');

        if (!in_array($role, ['admin', 'editor', 'viewer'], true)) {
            throw new ValidationException(['role' => ['Role must be admin, editor, or viewer.']]);
        }

        $existingUser = $this->users->findByEmail(mb_strtolower($email));

        if ($existingUser !== null && $this->members->findMembership($team->id, (int) $existingUser['id']) !== null) {
            throw new ConflictException('This person is already a member of the team.');
        }

        if ($this->invitations->findPendingForEmail($team->id, mb_strtolower($email)) !== null) {
            throw new ConflictException('An invitation is already pending for this email address.');
        }

        $rawToken = $this->tokens->generate();

        $this->invitations->create([
            'uuid' => str_uuid4(),
            'team_id' => $team->id,
            'email' => mb_strtolower($email),
            'role' => $role,
            'token_hash' => $this->tokens->hash($rawToken),
            'invited_by' => $invitedByUserId,
            'expires_at' => now_utc()->modify('+7 days')->format('Y-m-d H:i:s'),
        ]);

        $inviteUrl = rtrim((string) config('app.url'), '/') . "/team-invitations/{$rawToken}";
        $this->mail->send($email, $email, "You've been invited to join a team", "<p>You've been invited to join the team \"{$team->name}\". <a href=\"{$inviteUrl}\">Accept the invitation</a>.</p>");

        $this->activityLogs->record($invitedByUserId, "Invited {$email} to the team as {$role}.", 'teams', $team->id);

        return ['email' => $email, 'role' => $role, 'expires_at' => now_utc()->modify('+7 days')->format('Y-m-d H:i:s')];
    }

    public function acceptInvitation(string $rawToken, int $acceptingUserId): Team
    {
        $tokenHash = $this->tokens->hash($rawToken);
        $invitation = $this->invitations->findValidByTokenHash($tokenHash);

        if ($invitation === null) {
            throw new NotFoundException('This invitation is invalid or has expired.');
        }

        $user = $this->users->find($acceptingUserId);

        if ($user === null || mb_strtolower($user['email']) !== $invitation['email']) {
            throw new AuthorizationException('This invitation was sent to a different email address.');
        }

        if ($this->members->findMembership((int) $invitation['team_id'], $acceptingUserId) === null) {
            $this->members->create([
                'team_id' => $invitation['team_id'],
                'user_id' => $acceptingUserId,
                'role' => $invitation['role'],
                'invited_by' => $invitation['invited_by'],
                'joined_at' => now_utc()->format('Y-m-d H:i:s'),
            ]);
        }

        $this->invitations->markAccepted((int) $invitation['id']);
        $this->activityLogs->record($acceptingUserId, 'Joined the team.', 'teams', (int) $invitation['team_id']);

        return Team::fromArray($this->teams->find((int) $invitation['team_id']));
    }

    public function removeMember(string $teamUuid, int $requestingUserId, int $targetUserId): void
    {
        $team = $this->getForMember($teamUuid, $requestingUserId);
        $this->requireRole($team->id, $requestingUserId, 'admin');

        $targetMembership = $this->members->findMembership($team->id, $targetUserId);

        if ($targetMembership === null) {
            throw new NotFoundException('This person is not a member of the team.');
        }

        if ($targetMembership['role'] === 'owner') {
            throw new AuthorizationException('The team owner cannot be removed.');
        }

        $this->members->removeMember($team->id, $targetUserId);
        $this->activityLogs->record($requestingUserId, "Removed a member from the team.", 'teams', $team->id);
    }

    public function updateMemberRole(string $teamUuid, int $requestingUserId, int $targetUserId, string $role): void
    {
        $team = $this->getForMember($teamUuid, $requestingUserId);
        $this->requireRole($team->id, $requestingUserId, 'admin');

        if (!in_array($role, ['admin', 'editor', 'viewer'], true)) {
            throw new ValidationException(['role' => ['Role must be admin, editor, or viewer.']]);
        }

        $targetMembership = $this->members->findMembership($team->id, $targetUserId);

        if ($targetMembership === null) {
            throw new NotFoundException('This person is not a member of the team.');
        }

        if ($targetMembership['role'] === 'owner') {
            throw new AuthorizationException("The team owner's role cannot be changed.");
        }

        $this->members->updateRole($team->id, $targetUserId, $role);
        $this->activityLogs->record($requestingUserId, "Changed a member's role to {$role}.", 'teams', $team->id);
    }

    public function memberRole(int $teamId, int $userId): ?string
    {
        $membership = $this->members->findMembership($teamId, $userId);

        return $membership !== null ? $membership['role'] : null;
    }

    private function requireMembership(int $teamId, int $userId): void
    {
        if ($this->members->findMembership($teamId, $userId) === null) {
            throw new AuthorizationException('You are not a member of this team.');
        }
    }

    private function requireRole(int $teamId, int $userId, string $minimumRole): void
    {
        $membership = $this->members->findMembership($teamId, $userId);

        if ($membership === null) {
            throw new AuthorizationException('You are not a member of this team.');
        }

        $currentRank = self::ROLE_RANK[$membership['role']] ?? 0;
        $requiredRank = self::ROLE_RANK[$minimumRole] ?? 99;

        if ($currentRank < $requiredRank) {
            throw new AuthorizationException('You do not have sufficient permissions for this action.');
        }
    }
}
