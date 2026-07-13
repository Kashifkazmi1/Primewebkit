<?php

declare(strict_types=1);

namespace App\Models;

final class Plan
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly float $monthlyPrice,
        public readonly float $yearlyPrice,
        public readonly string $currency,
        public readonly int $botsLimit,
        public readonly int $messagesLimit,
        public readonly int $knowledgeLimitMb,
        public readonly int $storageLimitMb,
        public readonly int $teamMembersLimit,
        public readonly bool $apiAccess,
        public readonly bool $analytics,
        public readonly bool $whiteLabel,
        public readonly bool $customDomain,
        public readonly bool $prioritySupport,
        public readonly bool $streaming,
        public readonly int $trialDays,
        public readonly bool $isActive,
        public readonly int $sortOrder,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            uuid: (string) $row['uuid'],
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            description: $row['description'] ?? null,
            monthlyPrice: (float) $row['monthly_price'],
            yearlyPrice: (float) $row['yearly_price'],
            currency: (string) $row['currency'],
            botsLimit: (int) $row['bots_limit'],
            messagesLimit: (int) $row['messages_limit'],
            knowledgeLimitMb: (int) $row['knowledge_limit_mb'],
            storageLimitMb: (int) $row['storage_limit_mb'],
            teamMembersLimit: (int) $row['team_members_limit'],
            apiAccess: (bool) $row['api_access'],
            analytics: (bool) $row['analytics'],
            whiteLabel: (bool) $row['white_label'],
            customDomain: (bool) $row['custom_domain'],
            prioritySupport: (bool) $row['priority_support'],
            streaming: (bool) $row['streaming'],
            trialDays: (int) $row['trial_days'],
            isActive: (bool) $row['is_active'],
            sortOrder: (int) $row['sort_order'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'monthly_price' => $this->monthlyPrice,
            'yearly_price' => $this->yearlyPrice,
            'currency' => $this->currency,
            'limits' => [
                'bots' => $this->botsLimit,
                'messages_per_month' => $this->messagesLimit,
                'knowledge_mb' => $this->knowledgeLimitMb,
                'storage_mb' => $this->storageLimitMb,
                'team_members' => $this->teamMembersLimit,
            ],
            'features' => [
                'api_access' => $this->apiAccess,
                'analytics' => $this->analytics,
                'white_label' => $this->whiteLabel,
                'custom_domain' => $this->customDomain,
                'priority_support' => $this->prioritySupport,
                'streaming' => $this->streaming,
            ],
            'trial_days' => $this->trialDays,
            'is_active' => $this->isActive,
        ];
    }
}
