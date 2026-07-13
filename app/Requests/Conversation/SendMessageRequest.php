<?php

declare(strict_types=1);

namespace App\Requests\Conversation;

use App\Requests\FormRequest;

final class SendMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'session_id' => 'required|string|max:64',
            'fingerprint' => 'required|string|max:255',
            'message' => 'required|string|min:1|max:8000',
        ];
    }
}
