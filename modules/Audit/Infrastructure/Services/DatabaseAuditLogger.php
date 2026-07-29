<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Services;

use Modules\Audit\Application\Contracts\AuditRepository;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;

final readonly class DatabaseAuditLogger implements AuditLogger
{
    public function __construct(
        private AuditRepository $auditRepository,
    ) {}

    public function log(AuditEntry $entry): void
    {
        $this->auditRepository->save($entry);
    }
}
