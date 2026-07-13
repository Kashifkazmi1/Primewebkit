<?php

declare(strict_types=1);

namespace App\Requests\Auth;

use App\Requests\FormRequest;

final class ResetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'password' => 'required|string|min:8|max:255|confirmed',
            'password_confirmation' => 'required|string',
        ];
    }
}
