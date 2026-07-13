<?php

declare(strict_types=1);

namespace App\Requests\Widget;

use App\Requests\FormRequest;

final class UpdateWidgetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'theme' => 'nullable|in:light,dark',
            'position' => 'nullable|in:bottom-right,bottom-left',
            'primary_color' => 'nullable|string|max:20',
            'greeting_message' => 'nullable|string|max:500',
            'placeholder_text' => 'nullable|string|max:150',
            'show_branding' => 'nullable|boolean',
            'custom_css' => 'nullable|string|max:20000',
            'allowed_domains' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ];
    }
}
