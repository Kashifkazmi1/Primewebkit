<?php

declare(strict_types=1);

namespace App\Requests\Coupon;

use App\Requests\FormRequest;

final class CreateCouponRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => 'required|string|min:3|max:50|alpha_dash',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'max_redemptions' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date',
        ];
    }
}
