<?php

declare(strict_types=1);

use Modules\Admin\Application\Commands\ExportAuditLogsCommand;
use Modules\Admin\Application\UseCases\ExportAuditLogsHandler;
use Modules\Audit\Application\Contracts\AuditRepository;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Modules\Foundation\Application\Responses\ExportResponse;
use Modules\Foundation\Infrastructure\Export\CsvWriter;
use Modules\Foundation\Infrastructure\Export\ExportFileWriter;

final class InMemoryAuditRepositoryForExport implements AuditRepository
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

final class FakeFileStorageForExport implements FileStorage
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

final class FakeAuditLoggerForExport implements AuditLogger
{
    /** @var list<AuditEntry> */
    public array $logged = [];

    public function log(AuditEntry $entry): void
    {
        $this->logged[] = $entry;
    }
}

it('exporta los registros de auditoria a csv y devuelve una url de descarga', function (): void {
    $auditLogs = new InMemoryAuditRepositoryForExport;
    $auditLogs->save(new AuditEntry(
        action: 'user.activated',
        userId: 'user-1',
        entity: 'User',
        entityId: 'user-1',
        metadata: ['source' => 'admin-panel'],
        id: 'entry-1',
        occurredAt: new DateTimeImmutable('2026-08-27T10:00:00+00:00'),
    ));
    $fileStorage = new FakeFileStorageForExport;
    $auditLogger = new FakeAuditLoggerForExport;
    $handler = new ExportAuditLogsHandler($auditLogs, new ExportFileWriter($fileStorage), new CsvWriter, $auditLogger);

    $response = $handler->handle(new ExportAuditLogsCommand);

    expect($response)->toBeInstanceOf(ExportResponse::class)
        ->and($response->rowCount)->toBe(1)
        ->and($response->format)->toBe('csv')
        ->and($fileStorage->stored)->toHaveCount(1);

    $storedCsv = array_values($fileStorage->stored)[0];
    expect($storedCsv)->toContain('user.activated')
        ->and($storedCsv)->toContain('entry-1');
});

it('registra una entrada de auditoria por cada exportacion', function (): void {
    $auditLogs = new InMemoryAuditRepositoryForExport;
    $fileStorage = new FakeFileStorageForExport;
    $auditLogger = new FakeAuditLoggerForExport;
    $handler = new ExportAuditLogsHandler($auditLogs, new ExportFileWriter($fileStorage), new CsvWriter, $auditLogger);

    $handler->handle(new ExportAuditLogsCommand);

    expect($auditLogger->logged)->toHaveCount(1)
        ->and($auditLogger->logged[0]->action)->toBe('export.audit_logs')
        ->and($auditLogger->logged[0]->metadata)->toBe(['row_count' => 0, 'format' => 'csv']);
});

it('exporta una lista vacia cuando no hay registros de auditoria', function (): void {
    $auditLogs = new InMemoryAuditRepositoryForExport;
    $fileStorage = new FakeFileStorageForExport;
    $handler = new ExportAuditLogsHandler($auditLogs, new ExportFileWriter($fileStorage), new CsvWriter, new FakeAuditLoggerForExport);

    $response = $handler->handle(new ExportAuditLogsCommand);

    expect($response->rowCount)->toBe(0);
    $storedCsv = array_values($fileStorage->stored)[0];
    expect($storedCsv)->toBe("id,action,user_id,entity,entity_id,ip,correlation_id,outcome,metadata,occurred_at\n");
});
