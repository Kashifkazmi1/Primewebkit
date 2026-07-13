<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Requests\Settings\UpdateSettingsRequest;
use App\Services\SettingsService;

final class AdminSettingsController
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function index(Request $request): Response
    {
        return JsonResponse::success($this->settings->allGrouped(), 'Settings retrieved successfully.');
    }

    public function update(Request $request): Response
    {
        $data = UpdateSettingsRequest::validate($request);

        foreach ($data as $key => $value) {
            $this->settings->set($key, $value);
        }

        return JsonResponse::success($this->settings->allGrouped(), 'Settings updated successfully.');
    }
}
