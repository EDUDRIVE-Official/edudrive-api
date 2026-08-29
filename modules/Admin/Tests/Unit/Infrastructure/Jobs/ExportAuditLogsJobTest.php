<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Admin\Infrastructure\Jobs\ExportAuditLogsJob;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Enums\AsyncJobStatus;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\Audit\Application\Contracts\AuditRepository;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Modules\Foundation\Infrastructure\Export\CsvWriter;
use Modules\Foundation\Infrastructure\Export\ExportFileWriter;

final class InMemoryAsyncJobRepositoryForAuditExportJob implements AsyncJobRepository
{
    /** @var array<string, AsyncJob> */
    public array $items = [];

    public function save(AsyncJob $job): void
    {
        $this->items[$job->id()->value()] = $job;
    }

    public function findById(AsyncJobId $id): ?AsyncJob
    {
        return $this->items[$id->value()] ?? null;
    }

    /** @return list<AsyncJob> */
    public function allCompletedOrFailedBefore(DateTimeImmutable $threshold): array
    {
        return [];
    }

    public function delete(AsyncJobId $id): void
    {
        unset($this->items[$id->value()]);
    }
}

final class InMemoryAuditRepositoryForExportJob implements AuditRepository
{
    /** @var list<AuditEntry> */
    public array $items = [];

    public function save(AuditEntry $entry): void
    {
        $this->items[] = $entry;
    }

    /** @return list<AuditEntry> */
    public function all(): array
    {
        return $this->items;
    }
}

final class FakeFileStorageForExportJob implements FileStorage
{
    /** @var array<string, string> */
    public array $stored = [];

    public function store(string $storagePath, string $localTmpPath): void
    {
        $contents = file_get_contents($localTmpPath);
        $this->stored[$storagePath] = $contents === false ? '' : $contents;
    }

    public function delete(string $storagePath): void
    {
        unset($this->stored[$storagePath]);
    }

    public function temporaryDownloadUrl(string $storagePath, DateTimeImmutable $expiresAt): string
    {
        return "https://minio.local/{$storagePath}?expires={$expiresAt->getTimestamp()}";
    }
}

final class FakeAuditLoggerForExportJob implements AuditLogger
{
    /** @var list<AuditEntry> */
    public array $logged = [];

    public function log(AuditEntry $entry): void
    {
        $this->logged[] = $entry;
    }
}

it('genera el csv de auditoria y completa el trabajo asincrono', function (): void {
    $auditLogs = new InMemoryAuditRepositoryForExportJob;
    $auditLogs->save(new AuditEntry(
        action: 'user.activated',
        userId: 'user-1',
        entity: 'User',
        entityId: 'user-1',
        metadata: ['source' => 'admin-panel'],
        id: 'entry-1',
        occurredAt: new DateTimeImmutable('2026-08-27T10:00:00+00:00'),
    ));
    $jobs = new InMemoryAsyncJobRepositoryForAuditExportJob;
    $asyncJobId = AsyncJobId::fromString((string) Str::uuid());
    $jobs->save(AsyncJob::request($asyncJobId, 'export.audit_logs', 'user-1'));
    $fileStorage = new FakeFileStorageForExportJob;
    $auditLogger = new FakeAuditLoggerForExportJob;

    (new ExportAuditLogsJob($asyncJobId->value()))->handle(
        $jobs,
        $auditLogs,
        new ExportFileWriter($fileStorage),
        new CsvWriter,
        $auditLogger,
    );

    $completed = $jobs->findById($asyncJobId);
    expect($completed?->status())->toBe(AsyncJobStatus::Completed)
        ->and($completed?->result()['row_count'])->toBe(1)
        ->and($completed?->result()['format'])->toBe('csv')
        ->and($completed?->result()['storage_path'])->toStartWith('exports/audit-logs/')
        ->and($fileStorage->stored)->toHaveCount(1)
        ->and($auditLogger->logged)->toHaveCount(1)
        ->and($auditLogger->logged[0]->action)->toBe('export.audit_logs');

    $storedCsv = array_values($fileStorage->stored)[0];
    expect($storedCsv)->toContain('user.activated')->toContain('entry-1');
});

it('no falla si el trabajo asincrono ya no existe', function (): void {
    $jobs = new InMemoryAsyncJobRepositoryForAuditExportJob;
    $asyncJobId = AsyncJobId::fromString((string) Str::uuid());
    $fileStorage = new FakeFileStorageForExportJob;

    (new ExportAuditLogsJob($asyncJobId->value()))->handle(
        $jobs,
        new InMemoryAuditRepositoryForExportJob,
        new ExportFileWriter($fileStorage),
        new CsvWriter,
        new FakeAuditLoggerForExportJob,
    );

    expect($fileStorage->stored)->toBeEmpty();
});
