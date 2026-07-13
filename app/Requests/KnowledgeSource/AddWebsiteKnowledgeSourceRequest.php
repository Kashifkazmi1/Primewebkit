<?php

declare(strict_types=1);

namespace App\Requests\KnowledgeSource;

use App\Requests\FormRequest;

final class AddWebsiteKnowledgeSourceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'start_url' => 'required|url|max:2048',
            'max_pages' => 'nullable|integer|min:1|max:100',
        ];
    }
}
