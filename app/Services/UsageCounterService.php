<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UsageCounterRepository;

final class UsageCounterService
{
    public function __construct(private readonly UsageCounterRepository $counters)
    {
    }

    public function incrementMessages(int $userId): void
    {
        $this->counters->increment($userId, 'messages', now_utc()->format('Y-m'));
    }

    public function incrementAiRequests(int $userId): void
    {
        $this->counters->increment($userId, 'ai_requests', now_utc()->format('Y-m'));
    }

    public function incrementApiRequests(int $userId): void
    {
        $this->counters->increment($userId, 'api_requests', now_utc()->format('Y-m'));
    }

    public function addKnowledgeMb(int $userId, float $mb): void
    {
        $this->counters->incrementClamped($userId, 'knowledge_mb', 'lifetime', (int) ceil($mb));
    }

    public function subtractKnowledgeMb(int $userId, float $mb): void
    {
        $this->counters->incrementClamped($userId, 'knowledge_mb', 'lifetime', -(int) ceil($mb));
    }

    public function addStorageMb(int $userId, float $mb): void
    {
        $this->counters->incrementClamped($userId, 'storage_mb', 'lifetime', (int) ceil($mb));
    }
}
