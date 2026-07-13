<?php

declare(strict_types=1);

namespace App\Requests\Subscription;

use App\Requests\FormRequest;

final class SubscribeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'plan_id' => 'required|string',
            'billing_cycle' => 'required|in:monthly,yearly',
            'coupon_code' => 'nullable|string|max:50',
        ];
    }
}
