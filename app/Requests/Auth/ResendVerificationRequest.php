<?php

declare(strict_types=1);

namespace App\Requests\Auth;

use App\Requests\FormRequest;

final class ResendVerificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:190',
        ];
    }
}
