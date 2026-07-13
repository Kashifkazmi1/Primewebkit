<?php

declare(strict_types=1);

namespace App\Repositories;

final class AuditLogRepository extends BaseRepository
{
    protected string $table = 'audit_logs';
    protected bool $usesSoftDeletes = false;

    public function record(
        ?int $userId,
        string $action,
        ?string $auditableType,
        ?int $auditableId,
        string $ipAddress,
        string $userAgent,
        array $metadata = []
    ): void {
        $this->create([
            'uuid' => str_uuid4(),
            'user_id' => $userId,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'ip_address' => $ipAddress,
            'user_agent' => mb_substr($userAgent, 0, 255),
            'metadata' => empty($metadata) ? null : json_encode($metadata),
            'created_at' => now_utc()->format('Y-m-d H:i:s'),
        ]);
    }
}
