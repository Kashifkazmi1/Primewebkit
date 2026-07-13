<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Models\User;
use App\Requests\Subscription\CancelSubscriptionRequest;
use App\Requests\Subscription\SubscribeRequest;
use App\Resources\PlanResource;
use App\Services\InvoiceService;
use App\Services\PlanLimitService;
use App\Services\PlanService;
use App\Services\SubscriptionService;

final class SubscriptionController
{
    public function __construct(
        private readonly PlanService $plans,
        private readonly SubscriptionService $subscriptions,
        private readonly InvoiceService $invoices,
        private readonly PlanLimitService $planLimits,
    ) {
    }

    public function plans(Request $request): Response
    {
        return JsonResponse::success(PlanResource::collection($this->plans->listActive()), 'Plans retrieved successfully.');
    }

    public function current(Request $request): Response
    {
        $user = $this->currentUser($request);
        $subscription = $this->subscriptions->currentForUser($user->id);

        return JsonResponse::success([
            'subscription' => $subscription?->toPublicArray(),
            'limits_and_usage' => $this->planLimits->limitsAndUsageFor($user->id),
        ], 'Current subscription retrieved successfully.');
    }

    public function history(Request $request): Response
    {
        $user = $this->currentUser($request);
        $history = $this->subscriptions->historyForUser($user->id);

        return JsonResponse::success(array_map(fn ($s) => $s->toPublicArray(), $history), 'Subscription history retrieved successfully.');
    }

    public function subscribe(Request $request): Response
    {
        $user = $this->currentUser($request);
        $data = SubscribeRequest::validate($request);

        $subscription = $this->subscriptions->subscribe($user->id, $data['plan_id'], $data['billing_cycle'], $data['coupon_code'] ?? null);

        return JsonResponse::created($subscription->toPublicArray(), 'Subscribed successfully.');
    }

    public function cancel(Request $request, string $uuid): Response
    {
        $user = $this->currentUser($request);
        $data = CancelSubscriptionRequest::validate($request);

        $subscription = $this->subscriptions->cancel($user->id, $uuid, $data['at_period_end'] ?? true);

        return JsonResponse::success($subscription->toPublicArray(), 'Subscription canceled successfully.');
    }

    public function invoices(Request $request): Response
    {
        $user = $this->currentUser($request);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $result = $this->invoices->paginateForUser($user->id, $page, $perPage);

        return JsonResponse::success(
            array_map(fn ($i) => $i->toPublicArray(), $result['data']),
            'Invoices retrieved successfully.',
            200,
            ['total' => $result['total'], 'page' => $result['page'], 'per_page' => $result['per_page'], 'last_page' => $result['last_page']]
        );
    }

    private function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $user;
    }
}
