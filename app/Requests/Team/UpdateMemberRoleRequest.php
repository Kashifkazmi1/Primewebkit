<?php

declare(strict_types=1);

namespace App\Requests\Team;

use App\Requests\FormRequest;

final class UpdateMemberRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'role' => 'required|in:admin,editor,viewer',
        ];
    }
}
