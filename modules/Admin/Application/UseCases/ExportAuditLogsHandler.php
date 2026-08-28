<?php

declare(strict_types=1);

namespace Modules\Admin\Application\UseCases;

use DateTimeInterface;
use Illuminate\Support\Str;
use Modules\Admin\Application\Commands\ExportAuditLogsCommand;
use Modules\Audit\Application\Contracts\AuditRepository;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Foundation\Application\Responses\ExportResponse;
use Modules\Foundation\Infrastructure\Export\CsvWriter;
use Modules\Foundation\Infrastructure\Export\ExportFileWriter;

final readonly class ExportAuditLogsHandler
{
    public function __construct(
        private AuditRepository $auditLogs,
        private ExportFileWriter $exportFileWriter,
        private CsvWriter $csvWriter,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(ExportAuditLogsCommand $command): ExportResponse
    {
        $entries = $this->auditLogs->all();

        $rows = array_map(
            static fn (AuditEntry $entry): array => [
                (string) $entry->id,
                $entry->action,
                (string) $entry->userId,
                (string) $entry->entity,
                (string) $entry->entityId,
                json_encode($entry->metadata, JSON_THROW_ON_ERROR),
                $entry->occurredAt?->format(DateTimeInterface::ATOM) ?? '',
            ],
            $entries,
        );

        $csv = $this->csvWriter->toString(
            ['id', 'action', 'user_id', 'entity', 'entity_id', 'metadata', 'occurred_at'],
            $rows,
        );

        $storagePath = sprintf('exports/audit-logs/%s.csv', (string) Str::uuid());
        $exported = $this->exportFileWriter->write($storagePath, $csv);

        $this->auditLogger->log(new AuditEntry(
            action: 'export.audit_logs',
            metadata: ['row_count' => count($rows), 'format' => 'csv'],
        ));

        return new ExportResponse(
            url: $exported->url,
            expiresAt: $exported->expiresAt->format(DateTimeInterface::ATOM),
            rowCount: count($rows),
            format: 'csv',
        );
    }
}
