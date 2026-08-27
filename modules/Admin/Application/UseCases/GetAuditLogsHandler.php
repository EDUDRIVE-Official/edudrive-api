<?php

declare(strict_types=1);

namespace Modules\Admin\Application\UseCases;

use Modules\Admin\Application\Queries\GetAuditLogsQuery;
use Modules\Admin\Application\Responses\AuditLogResponse;
use Modules\Audit\Application\Contracts\AuditRepository;
use Modules\Audit\Application\DTO\AuditEntry;

final readonly class GetAuditLogsHandler
{
    public function __construct(private AuditRepository $auditLogs) {}

    /** @return list<AuditLogResponse> */
    public function handle(GetAuditLogsQuery $query): array
    {
        return array_map(
            static fn (AuditEntry $entry): AuditLogResponse => AuditLogResponse::fromAuditEntry($entry),
            $this->auditLogs->all(),
        );
    }
}
