<?php

declare(strict_types=1);

namespace App\Controllers\Team;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Models\User;
use App\Requests\Team\CreateTeamRequest;
use App\Requests\Team\InviteMemberRequest;
use App\Requests\Team\UpdateMemberRoleRequest;
use App\Resources\TeamResource;
use App\Services\TeamService;

final class TeamController
{
    public function __construct(private readonly TeamService $teams)
    {
    }

    public function index(Request $request): Response
    {
        $user = $this->currentUser($request);

        return JsonResponse::success($this->teams->myTeams($user->id), 'Teams retrieved successfully.');
    }

    public function store(Request $request): Response
    {
        $user = $this->currentUser($request);
        $data = CreateTeamRequest::validate($request);

        $team = $this->teams->create($user->id, $data['name']);

        return JsonResponse::created(TeamResource::make($team), 'Team created successfully.');
    }

    public function show(Request $request, string $uuid): Response
    {
        $user = $this->currentUser($request);
        $team = $this->teams->getForMember($uuid, $user->id);

        return JsonResponse::success(TeamResource::make($team), 'Team retrieved successfully.');
    }

    public function members(Request $request, string $uuid): Response
    {
        $user = $this->currentUser($request);
        $members = $this->teams->membersOf($uuid, $user->id);

        $sanitized = array_map(fn (array $m) => [
            'user_id' => $m['user_id'],
            'name' => $m['name'],
            'email' => $m['email'],
            'role' => $m['role'],
            'joined_at' => $m['joined_at'],
        ], $members);

        return JsonResponse::success($sanitized, 'Team members retrieved successfully.');
    }

    public function invite(Request $request, string $uuid): Response
    {
        $user = $this->currentUser($request);
        $data = InviteMemberRequest::validate($request);

        $result = $this->teams->invite($uuid, $user->id, $data['email'], $data['role']);

        return JsonResponse::created($result, 'Invitation sent successfully.');
    }

    public function acceptInvitation(Request $request, string $token): Response
    {
        $user = $this->currentUser($request);
        $team = $this->teams->acceptInvitation($token, $user->id);

        return JsonResponse::success(TeamResource::make($team), 'Invitation accepted successfully. Welcome to the team!');
    }

    public function removeMember(Request $request, string $uuid, string $targetUserId): Response
    {
        $user = $this->currentUser($request);
        $this->teams->removeMember($uuid, $user->id, (int) $targetUserId);

        return JsonResponse::success(null, 'Member removed successfully.');
    }

    public function updateMemberRole(Request $request, string $uuid, string $targetUserId): Response
    {
        $user = $this->currentUser($request);
        $data = UpdateMemberRoleRequest::validate($request);

        $this->teams->updateMemberRole($uuid, $user->id, (int) $targetUserId, $data['role']);

        return JsonResponse::success(null, 'Member role updated successfully.');
    }

    private function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $user;
    }
}
