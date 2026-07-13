<?php

declare(strict_types=1);

namespace App\Requests\Webhook;

use App\Requests\FormRequest;

final class CreateWebhookRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'url' => 'required|url|max:2048',
            'events' => 'required|array',
        ];
    }
}
