<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Models\User;
use App\Services\Admin\AdminUserService;

final class AdminUserController
{
    public function __construct(private readonly AdminUserService $adminUsers)
    {
    }

    public function index(Request $request): Response
    {
        $query = $request->query('q');
        $status = $request->query('status');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $result = $this->adminUsers->search(is_string($query) ? $query : null, is_string($status) ? $status : null, $page, $perPage);

        return JsonResponse::success($result['data'], 'Users retrieved successfully.', 200, [
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'last_page' => $result['last_page'],
        ]);
    }

    public function suspend(Request $request, string $uuid): Response
    {
        $this->adminUsers->suspend($uuid, $this->currentAdmin($request)->id);

        return JsonResponse::success(null, 'User suspended successfully.');
    }

    public function activate(Request $request, string $uuid): Response
    {
        $this->adminUsers->activate($uuid, $this->currentAdmin($request)->id);

        return JsonResponse::success(null, 'User activated successfully.');
    }

    public function destroy(Request $request, string $uuid): Response
    {
        $this->adminUsers->delete($uuid, $this->currentAdmin($request)->id);

        return JsonResponse::success(null, 'User deleted successfully.');
    }

    public function resetPassword(Request $request, string $uuid): Response
    {
        $resetUrlTemplate = rtrim((string) config('app.url'), '/') . '/reset-password?token={token}';
        $this->adminUsers->triggerPasswordReset($uuid, $this->currentAdmin($request)->id, $resetUrlTemplate);

        return JsonResponse::success(null, 'Password reset email sent to the user.');
    }

    public function forceLogout(Request $request, string $uuid): Response
    {
        $this->adminUsers->forceLogout($uuid, $this->currentAdmin($request)->id);

        return JsonResponse::success(null, 'User has been logged out of all devices.');
    }

    public function activity(Request $request, string $uuid): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $result = $this->adminUsers->activity($uuid, $page, $perPage);

        return JsonResponse::success($result['data'], 'User activity retrieved successfully.', 200, [
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'last_page' => $result['last_page'],
        ]);
    }

    public function loginHistory(Request $request, string $uuid): Response
    {
        return JsonResponse::success($this->adminUsers->loginHistory($uuid), 'Login history retrieved successfully.');
    }

    public function apiUsage(Request $request, string $uuid): Response
    {
        return JsonResponse::success($this->adminUsers->apiUsage($uuid), 'API usage retrieved successfully.');
    }

    public function aiUsage(Request $request, string $uuid): Response
    {
        return JsonResponse::success($this->adminUsers->aiUsage($uuid), 'AI usage retrieved successfully.');
    }

    public function storageUsage(Request $request, string $uuid): Response
    {
        return JsonResponse::success($this->adminUsers->storageUsage($uuid), 'Storage usage retrieved successfully.');
    }

    private function currentAdmin(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $user;
    }
}
