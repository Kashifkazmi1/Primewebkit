<?php

declare(strict_types=1);

namespace App\Requests\Team;

use App\Requests\FormRequest;

final class InviteMemberRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:190',
            'role' => 'required|in:admin,editor,viewer',
        ];
    }
}
