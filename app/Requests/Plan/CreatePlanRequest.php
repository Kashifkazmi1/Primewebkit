<?php

declare(strict_types=1);

namespace App\Requests\Plan;

use App\Requests\FormRequest;

final class CreatePlanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100',
            'slug' => 'nullable|string|max:100|alpha_dash',
            'description' => 'nullable|string|max:1000',
            'monthly_price' => 'nullable|numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'bots_limit' => 'nullable|integer|min:-1',
            'messages_limit' => 'nullable|integer|min:-1',
            'knowledge_limit_mb' => 'nullable|integer|min:-1',
            'storage_limit_mb' => 'nullable|integer|min:-1',
            'team_members_limit' => 'nullable|integer|min:-1',
            'api_access' => 'nullable|boolean',
            'analytics' => 'nullable|boolean',
            'white_label' => 'nullable|boolean',
            'custom_domain' => 'nullable|boolean',
            'priority_support' => 'nullable|boolean',
            'streaming' => 'nullable|boolean',
            'trial_days' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ];
    }
}
