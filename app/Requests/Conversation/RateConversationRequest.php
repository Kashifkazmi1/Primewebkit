<?php

declare(strict_types=1);

namespace App\Requests\Conversation;

use App\Requests\FormRequest;

final class RateConversationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }
}
