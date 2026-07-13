<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\UsageLimitExceededException;
use App\Repositories\WhiteLabelRepository;

final class WhiteLabelService
{
    public function __construct(
        private readonly WhiteLabelRepository $whiteLabel,
        private readonly PlanLimitService $planLimits,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getFor(int $userId): array
    {
        $row = $this->whiteLabel->findForUser($userId);

        return $row ?? [
            'logo_path' => null,
            'primary_color' => null,
            'secondary_color' => null,
            'custom_domain' => null,
            'remove_branding' => false,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function updateFor(int $userId, array $data): array
    {
        if (!$this->planLimits->hasFeature($userId, 'white_label')) {
            throw new UsageLimitExceededException(
                'White-label branding is not included in your current plan. Please upgrade to customize branding.',
                'white_label'
            );
        }

        $allowed = array_intersect_key($data, array_flip(['logo_path', 'primary_color', 'secondary_color', 'custom_domain', 'remove_branding']));

        if (isset($allowed['remove_branding'])) {
            $allowed['remove_branding'] = !empty($allowed['remove_branding']) ? 1 : 0;
        }

        $existing = $this->whiteLabel->findForUser($userId);

        if ($existing === null) {
            $this->whiteLabel->create(array_merge(['user_id' => $userId], $allowed));
        } else {
            $this->whiteLabel->update((int) $existing['id'], $allowed);
        }

        return $this->getFor($userId);
    }
}
