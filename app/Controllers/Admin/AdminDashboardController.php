<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\Admin\AdminDashboardService;

final class AdminDashboardController
{
    public function __construct(private readonly AdminDashboardService $dashboard)
    {
    }

    public function overview(Request $request): Response
    {
        return JsonResponse::success($this->dashboard->overview(), 'Dashboard overview retrieved successfully.');
    }
}
