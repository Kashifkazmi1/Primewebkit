<?php

declare(strict_types=1);

namespace App\Requests\Settings;

use App\Requests\FormRequest;

final class UpdateSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'platform.name' => 'nullable|string|max:150',
            'platform.support_email' => 'nullable|email|max:190',
            'platform.maintenance_mode' => 'nullable|boolean',
            'branding.logo_url' => 'nullable|string|max:2048',
            'branding.primary_color' => 'nullable|string|max:20',
            'uploads.max_file_size_mb' => 'nullable|integer|min:1|max:100',
            'limits.default_bots' => 'nullable|integer|min:0',
            'security.login_max_attempts' => 'nullable|integer|min:1|max:20',
            'security.login_lockout_minutes' => 'nullable|integer|min:1|max:1440',
        ];
    }
}
