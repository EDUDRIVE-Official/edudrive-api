<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Modules\Audit\Application\Contracts\AuditRepository;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;

final readonly class DatabaseAuditLogger implements AuditLogger
{
    public function __construct(
        private AuditRepository $auditRepository,
        private Request $request,
    ) {}

    public function log(AuditEntry $entry): void
    {
        /** @var string|null $correlationId */
        $correlationId = $entry->correlationId ?? Context::get('correlation_id');

        $this->auditRepository->save(new AuditEntry(
            action: $entry->action,
            userId: $entry->userId,
            entity: $entry->entity,
            entityId: $entry->entityId,
            metadata: $entry->metadata,
            ip: $entry->ip ?? $this->request->ip(),
            correlationId: $correlationId,
            outcome: $entry->outcome,
        ));
    }
}
