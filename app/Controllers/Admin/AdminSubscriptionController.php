<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\NotFoundException;
use App\Repositories\CouponRepository;
use App\Repositories\TransactionRepository;
use App\Requests\Coupon\CreateCouponRequest;
use App\Resources\InvoiceResource;
use App\Services\InvoiceService;

final class AdminSubscriptionController
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly TransactionRepository $transactions,
        private readonly CouponRepository $coupons,
    ) {
    }

    public function pendingInvoices(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $result = $this->invoices->paginatePending($page, $perPage);

        return JsonResponse::success(InvoiceResource::collection($result['data']), 'Pending invoices retrieved successfully.', 200, [
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'last_page' => $result['last_page'],
        ]);
    }

    public function markInvoicePaid(Request $request, string $uuid): Response
    {
        $invoice = $this->invoices->markPaid($uuid);

        return JsonResponse::success(InvoiceResource::make($invoice), 'Invoice marked as paid.');
    }

    public function voidInvoice(Request $request, string $uuid): Response
    {
        $invoice = $this->invoices->void($uuid);

        return JsonResponse::success(InvoiceResource::make($invoice), 'Invoice voided.');
    }

    public function createCoupon(Request $request): Response
    {
        $data = CreateCouponRequest::validate($request);

        $id = (int) $this->coupons->create([
            'uuid' => str_uuid4(),
            'code' => strtoupper($data['code']),
            'type' => $data['type'],
            'value' => $data['value'],
            'max_redemptions' => $data['max_redemptions'] ?? null,
            'valid_from' => $data['valid_from'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'is_active' => 1,
        ]);

        return JsonResponse::created($this->coupons->find($id), 'Coupon created successfully.');
    }

    public function listCoupons(Request $request): Response
    {
        return JsonResponse::success($this->coupons->all(), 'Coupons retrieved successfully.');
    }

    public function deactivateCoupon(Request $request, string $uuid): Response
    {
        $coupon = $this->coupons->findByUuid($uuid);

        if ($coupon === null) {
            throw new NotFoundException('Coupon not found.');
        }

        $this->coupons->update((int) $coupon['id'], ['is_active' => 0]);

        return JsonResponse::success(null, 'Coupon deactivated successfully.');
    }
}
