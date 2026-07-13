<?php

declare(strict_types=1);

namespace App\Requests\KnowledgeSource;

use App\Requests\FormRequest;

final class AddQaKnowledgeSourceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'question' => 'required|string|min:2|max:500',
            'answer' => 'required|string|min:1|max:5000',
        ];
    }
}
