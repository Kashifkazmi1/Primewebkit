<?php

declare(strict_types=1);

namespace App\Requests\Auth;

use App\Requests\FormRequest;

final class RefreshTokenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'refresh_token' => 'required|string',
        ];
    }
}
