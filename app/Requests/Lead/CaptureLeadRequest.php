<?php

declare(strict_types=1);

namespace App\Requests\Lead;

use App\Requests\FormRequest;

final class CaptureLeadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:190',
            'phone' => 'nullable|string|max:30',
            'session_id' => 'required|string|max:64',
        ];
    }
}
