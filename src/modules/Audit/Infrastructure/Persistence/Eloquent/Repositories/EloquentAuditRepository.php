<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Persistence\Eloquent\Repositories;

use Carbon\CarbonImmutable;
use Modules\Audit\Application\Contracts\AuditRepository;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;

final class EloquentAuditRepository implements AuditRepository
{
    public function save(AuditEntry $entry): void
    {
        AuditLogModel::query()->create([
            'user_id' => $entry->userId,
            'action' => $entry->action,
            'entity' => $entry->entity,
            'entity_id' => $entry->entityId,
            'metadata' => $entry->metadata,
            'occurred_at' => CarbonImmutable::now(),
        ]);
    }
}
