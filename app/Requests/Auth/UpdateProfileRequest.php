<?php

declare(strict_types=1);

namespace App\Requests\Auth;

use App\Requests\FormRequest;

final class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|min:2|max:150',
            'timezone' => 'nullable|string|max:64',
            'locale' => 'nullable|string|max:10',
        ];
    }
}
