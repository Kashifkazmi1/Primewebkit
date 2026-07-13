<?php

declare(strict_types=1);

namespace App\Requests\Auth;

use App\Requests\FormRequest;

final class DeleteAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'password' => 'required|string',
        ];
    }
}
