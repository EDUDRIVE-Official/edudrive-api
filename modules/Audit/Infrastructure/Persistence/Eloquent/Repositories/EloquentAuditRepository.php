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
            'ip' => $entry->ip,
            'correlation_id' => $entry->correlationId,
            'outcome' => $entry->outcome,
            'metadata' => $entry->metadata,
            'occurred_at' => CarbonImmutable::now(),
        ]);
    }

    /** @return list<AuditEntry> */
    public function all(): array
    {
        return array_values(
            AuditLogModel::query()
                ->orderByDesc('occurred_at')
                ->get()
                ->map(fn (AuditLogModel $model): AuditEntry => new AuditEntry(
                    action: $model->action,
                    userId: $model->user_id,
                    entity: $model->entity,
                    entityId: $model->entity_id,
                    metadata: $model->metadata ?? [],
                    ip: $model->ip,
                    correlationId: $model->correlation_id,
                    outcome: $model->outcome,
                    id: $model->id,
                    occurredAt: $model->occurred_at->toDateTimeImmutable(),
                ))
                ->all(),
        );
    }
}
