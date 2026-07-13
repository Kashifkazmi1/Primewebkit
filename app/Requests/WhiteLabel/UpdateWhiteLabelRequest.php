<?php

declare(strict_types=1);

namespace App\Requests\WhiteLabel;

use App\Requests\FormRequest;

final class UpdateWhiteLabelRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'logo_path' => 'nullable|string|max:255',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'custom_domain' => 'nullable|string|max:255',
            'remove_branding' => 'nullable|boolean',
        ];
    }
}
