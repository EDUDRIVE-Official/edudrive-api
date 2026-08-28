<?php

declare(strict_types=1);

namespace Modules\Admin\Application\UseCases;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Str;
use Modules\Admin\Application\Commands\ExportAuditLogsCommand;
use Modules\Audit\Application\Contracts\AuditRepository;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Modules\Foundation\Application\Responses\ExportResponse;
use Modules\Foundation\Infrastructure\Export\CsvWriter;
use RuntimeException;

final readonly class ExportAuditLogsHandler
{
    private const int URL_LIFETIME_MINUTES = 15;

    public function __construct(
        private AuditRepository $auditLogs,
        private FileStorage $fileStorage,
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
        $tmpPath = tempnam(sys_get_temp_dir(), 'export_');
        if ($tmpPath === false) {
            throw new RuntimeException('No se pudo crear un archivo temporal para la exportación.');
        }

        file_put_contents($tmpPath, $csv);

        try {
            $this->fileStorage->store($storagePath, $tmpPath);
        } finally {
            unlink($tmpPath);
        }

        $expiresAt = new DateTimeImmutable('+'.self::URL_LIFETIME_MINUTES.' minutes');
        $url = $this->fileStorage->temporaryDownloadUrl($storagePath, $expiresAt);

        $this->auditLogger->log(new AuditEntry(
            action: 'export.audit_logs',
            metadata: ['row_count' => count($rows), 'format' => 'csv'],
        ));

        return new ExportResponse(
            url: $url,
            expiresAt: $expiresAt->format(DateTimeInterface::ATOM),
            rowCount: count($rows),
            format: 'csv',
        );
    }
}
