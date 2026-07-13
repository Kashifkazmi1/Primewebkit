<?php

declare(strict_types=1);

namespace App\Requests\KnowledgeSource;

use App\Requests\FormRequest;

final class AddTextKnowledgeSourceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'source_name' => 'required|string|min:2|max:255',
            'content' => 'required|string|min:1|max:500000',
        ];
    }
}
