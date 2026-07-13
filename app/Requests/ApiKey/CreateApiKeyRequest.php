<?php

declare(strict_types=1);

namespace App\Requests\ApiKey;

use App\Requests\FormRequest;

final class CreateApiKeyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:150',
            'expires_at' => 'nullable|date',
            'scopes' => 'nullable|array',
        ];
    }
}
