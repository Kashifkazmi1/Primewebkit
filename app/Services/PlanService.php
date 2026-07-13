<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Models\Plan;
use App\Repositories\PlanRepository;
use App\Repositories\SubscriptionRepository;

final class PlanService
{
    public function __construct(
        private readonly PlanRepository $plans,
        private readonly SubscriptionRepository $subscriptions,
    ) {
    }

    /**
     * @return list<Plan>
     */
    public function listActive(): array
    {
        return array_map(Plan::fromArray(...), $this->plans->allActive());
    }

    /**
     * @return list<Plan>
     */
    public function listAll(): array
    {
        return array_map(Plan::fromArray(...), $this->plans->all());
    }

    public function find(string $uuid): Plan
    {
        $row = $this->plans->findByUuid($uuid);

        if ($row === null) {
            throw new NotFoundException('Plan not found.');
        }

        return Plan::fromArray($row);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Plan
    {
        $slug = $data['slug'] ?? $this->slugify($data['name']);

        if ($this->plans->findBySlug($slug) !== null) {
            throw new ConflictException('A plan with this slug already exists.');
        }

        $id = (int) $this->plans->create([
            'uuid' => str_uuid4(),
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'monthly_price' => $data['monthly_price'] ?? 0,
            'yearly_price' => $data['yearly_price'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'bots_limit' => $data['bots_limit'] ?? 1,
            'messages_limit' => $data['messages_limit'] ?? 500,
            'knowledge_limit_mb' => $data['knowledge_limit_mb'] ?? 10,
            'storage_limit_mb' => $data['storage_limit_mb'] ?? 100,
            'team_members_limit' => $data['team_members_limit'] ?? 1,
            'api_access' => !empty($data['api_access']) ? 1 : 0,
            'analytics' => !empty($data['analytics']) ? 1 : 0,
            'white_label' => !empty($data['white_label']) ? 1 : 0,
            'custom_domain' => !empty($data['custom_domain']) ? 1 : 0,
            'priority_support' => !empty($data['priority_support']) ? 1 : 0,
            'streaming' => array_key_exists('streaming', $data) ? (!empty($data['streaming']) ? 1 : 0) : 1,
            'trial_days' => $data['trial_days'] ?? 0,
            'is_active' => array_key_exists('is_active', $data) ? (!empty($data['is_active']) ? 1 : 0) : 1,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return Plan::fromArray($this->plans->find($id));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $uuid, array $data): Plan
    {
        $plan = $this->find($uuid);

        $allowed = array_intersect_key($data, array_flip([
            'name', 'description', 'monthly_price', 'yearly_price', 'currency',
            'bots_limit', 'messages_limit', 'knowledge_limit_mb', 'storage_limit_mb', 'team_members_limit',
            'api_access', 'analytics', 'white_label', 'custom_domain', 'priority_support', 'streaming',
            'trial_days', 'is_active', 'sort_order',
        ]));

        foreach (['api_access', 'analytics', 'white_label', 'custom_domain', 'priority_support', 'streaming', 'is_active'] as $boolField) {
            if (array_key_exists($boolField, $allowed)) {
                $allowed[$boolField] = !empty($allowed[$boolField]) ? 1 : 0;
            }
        }

        if (!empty($allowed)) {
            $this->plans->update($plan->id, $allowed);
        }

        return Plan::fromArray($this->plans->find($plan->id));
    }

    public function delete(string $uuid): void
    {
        $plan = $this->find($uuid);

        if ($this->subscriptions->countActiveForPlan($plan->id) > 0) {
            throw new ConflictException('This plan has active subscriptions and cannot be deleted.');
        }

        $this->plans->delete($plan->id);
    }

    private function slugify(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? $slug;

        return trim($slug, '-');
    }
}
