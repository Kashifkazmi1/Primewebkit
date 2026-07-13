<?php

declare(strict_types=1);

namespace App\Requests\Team;

use App\Requests\FormRequest;

final class CreateTeamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:150',
        ];
    }
}
