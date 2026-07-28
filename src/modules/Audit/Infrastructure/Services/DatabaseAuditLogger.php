<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Services;

use Carbon\CarbonImmutable;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;

final class DatabaseAuditLogger implements AuditLogger
{
    public function log(AuditEntry $entry): void
    {
        AuditLogModel::create([
            'user_id' => $entry->userId,
            'action' => $entry->action,
            'entity' => $entry->entity,
            'entity_id' => $entry->entityId,
            'metadata' => $entry->metadata,
            'occurred_at' => CarbonImmutable::now(),
        ]);
    }
}
