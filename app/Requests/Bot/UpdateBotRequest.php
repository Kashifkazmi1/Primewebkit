<?php

declare(strict_types=1);

namespace App\Requests\Bot;

use App\Requests\FormRequest;

final class UpdateBotRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|min:2|max:150',
            'description' => 'nullable|string|max:1000',
            'system_prompt' => 'nullable|string|max:8000',
            'model' => 'nullable|string|max:100',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_output_tokens' => 'nullable|integer|min:64|max:8192',
            'top_p' => 'nullable|numeric|min:0|max:1',
            'top_k' => 'nullable|integer|min:1|max:100',
            'safety_settings' => 'nullable|array',
            'language' => 'nullable|string|max:10',
            'personality' => 'nullable|string|max:255',
            'tone' => 'nullable|string|max:50',
            'welcome_message' => 'nullable|string|max:500',
            'primary_color' => 'nullable|string|max:20',
            'status' => 'nullable|in:draft,training,active,archived',
            'is_public' => 'nullable|boolean',
        ];
    }
}