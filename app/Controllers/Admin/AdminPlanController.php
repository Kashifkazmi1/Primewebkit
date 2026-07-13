<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Requests\Plan\CreatePlanRequest;
use App\Requests\Plan\UpdatePlanRequest;
use App\Resources\PlanResource;
use App\Services\PlanService;

final class AdminPlanController
{
    public function __construct(private readonly PlanService $plans)
    {
    }

    public function index(Request $request): Response
    {
        return JsonResponse::success(PlanResource::collection($this->plans->listAll()), 'Plans retrieved successfully.');
    }

    public function store(Request $request): Response
    {
        $data = CreatePlanRequest::validate($request);
        $plan = $this->plans->create($data);

        return JsonResponse::created(PlanResource::make($plan), 'Plan created successfully.');
    }

    public function show(Request $request, string $uuid): Response
    {
        return JsonResponse::success(PlanResource::make($this->plans->find($uuid)), 'Plan retrieved successfully.');
    }

    public function update(Request $request, string $uuid): Response
    {
        $data = UpdatePlanRequest::validate($request);
        $plan = $this->plans->update($uuid, $data);

        return JsonResponse::success(PlanResource::make($plan), 'Plan updated successfully.');
    }

    public function destroy(Request $request, string $uuid): Response
    {
        $this->plans->delete($uuid);

        return JsonResponse::success(null, 'Plan deleted successfully.');
    }
}
