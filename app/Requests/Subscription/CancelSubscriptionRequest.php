<?php

declare(strict_types=1);

namespace App\Requests\Subscription;

use App\Requests\FormRequest;

final class CancelSubscriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'at_period_end' => 'nullable|boolean',
        ];
    }
}
