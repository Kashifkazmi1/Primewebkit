<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LeadRepository;

final class LeadService
{
    public function __construct(
        private readonly LeadRepository $leads,
        private readonly WebhookDispatcherService $webhooks,
    ) {
    }

    /**
     * @param array{name?: string, email?: string, phone?: string} $data
     */
    public function capture(int $botId, ?int $conversationId, array $data): array
    {
        $id = (int) $this->leads->create([
            'uuid' => str_uuid4(),
            'bot_id' => $botId,
            'conversation_id' => $conversationId,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        $lead = $this->leads->find($id);

        $this->webhooks->dispatch('lead.created', [
            'lead_id' => $lead['uuid'],
            'bot_id' => $botId,
            'name' => $lead['name'],
            'email' => $lead['email'],
        ]);

        return $lead;
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginateForBot(int $botId, int $page, int $perPage): array
    {
        return $this->leads->paginateForBot($botId, $page, $perPage);
    }
}
