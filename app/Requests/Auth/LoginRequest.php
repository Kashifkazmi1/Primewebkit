<?php

declare(strict_types=1);

namespace App\Requests\Auth;

use App\Requests\FormRequest;

final class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:190',
            'password' => 'required|string',
        ];
    }
}
