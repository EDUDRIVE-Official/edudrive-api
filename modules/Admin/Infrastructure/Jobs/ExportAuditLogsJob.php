<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Jobs;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\Audit\Application\Contracts\AuditRepository;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Foundation\Infrastructure\Export\CsvWriter;
use Modules\Foundation\Infrastructure\Export\ExportFileWriter;
use Throwable;

final class ExportAuditLogsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $asyncJobId,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(
        AsyncJobRepository $jobs,
        AuditRepository $auditLogs,
        ExportFileWriter $exportFileWriter,
        CsvWriter $csvWriter,
        AuditLogger $auditLogger,
    ): void {
        $job = $jobs->findById(AsyncJobId::fromString($this->asyncJobId));
        if ($job === null) {
            return;
        }

        $job->start(new DateTimeImmutable('now'));
        $jobs->save($job);

        $entries = $auditLogs->all();

        $rows = array_map(
            static fn (AuditEntry $entry): array => [
                (string) $entry->id,
                $entry->action,
                (string) $entry->userId,
                (string) $entry->entity,
                (string) $entry->entityId,
                (string) $entry->ip,
                (string) $entry->correlationId,
                $entry->outcome,
                json_encode($entry->metadata, JSON_THROW_ON_ERROR),
                $entry->occurredAt?->format(DateTimeInterface::ATOM) ?? '',
            ],
            $entries,
        );

        $csv = $csvWriter->toString(
            ['id', 'action', 'user_id', 'entity', 'entity_id', 'ip', 'correlation_id', 'outcome', 'metadata', 'occurred_at'],
            $rows,
        );

        $storagePath = sprintf('exports/audit-logs/%s.csv', (string) Str::uuid());
        $exported = $exportFileWriter->write($storagePath, $csv);

        $auditLogger->log(new AuditEntry(
            action: 'export.audit_logs',
            metadata: ['row_count' => count($rows), 'format' => 'csv'],
        ));

        $job->complete([
            'url' => $exported->url,
            'expires_at' => $exported->expiresAt->format(DateTimeInterface::ATOM),
            'row_count' => count($rows),
            'format' => 'csv',
            'storage_path' => $storagePath,
        ], new DateTimeImmutable('now'));
        $jobs->save($job);
    }

    public function failed(?Throwable $exception): void
    {
        $jobs = app(AsyncJobRepository::class);
        $job = $jobs->findById(AsyncJobId::fromString($this->asyncJobId));
        if ($job === null) {
            return;
        }

        $job->fail($exception?->getMessage() ?? 'Error desconocido al exportar la auditoria.', new DateTimeImmutable('now'));
        $jobs->save($job);

        Log::warning('Fallo la exportacion asincrona de logs de auditoria.', [
            'async_job_id' => $this->asyncJobId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
