<?php

declare(strict_types=1);

namespace App\Requests\Auth;

use App\Requests\FormRequest;

final class ChangePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|max:255|confirmed',
            'new_password_confirmation' => 'required|string',
        ];
    }
}
