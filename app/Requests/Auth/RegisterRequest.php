<?php

declare(strict_types=1);

namespace App\Requests\Auth;

use App\Requests\FormRequest;

final class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:150',
            'email' => 'required|email|max:190|unique:users,email',
            'password' => 'required|string|min:8|max:255|confirmed',
            'password_confirmation' => 'required|string',
            'timezone' => 'nullable|string|max:64',
            'locale' => 'nullable|string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
            'email.unique' => 'An account with this email address already exists.',
        ];
    }
}
