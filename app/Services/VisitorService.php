<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\VisitorRepository;

final class VisitorService
{
    public function __construct(private readonly VisitorRepository $visitors)
    {
    }

    /**
     * Finds or creates a visitor record for a given bot, identified by
     * a client-provided fingerprint (e.g. a random ID persisted in the
     * embed script's local storage). Returns the internal visitor id.
     */
    public function findOrCreate(int $botId, string $fingerprint, string $ip, string $userAgent): int
    {
        $fingerprintHash = hash('sha256', $fingerprint);
        $existing = $this->visitors->findByFingerprint($botId, $fingerprintHash);

        if ($existing !== null) {
            $this->visitors->touch((int) $existing['id']);

            return (int) $existing['id'];
        }

        return (int) $this->visitors->create([
            'uuid' => str_uuid4(),
            'bot_id' => $botId,
            'fingerprint' => $fingerprintHash,
            'ip_address' => $ip,
            'user_agent' => mb_substr($userAgent, 0, 255),
            'first_seen_at' => now_utc()->format('Y-m-d H:i:s'),
            'last_seen_at' => now_utc()->format('Y-m-d H:i:s'),
        ]);
    }
}
