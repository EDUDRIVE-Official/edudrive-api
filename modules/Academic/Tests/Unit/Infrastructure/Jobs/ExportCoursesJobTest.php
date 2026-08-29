<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Infrastructure\Jobs\ExportCoursesJob;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Enums\AsyncJobStatus;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Modules\Foundation\Infrastructure\Export\CsvWriter;
use Modules\Foundation\Infrastructure\Export\ExportFileWriter;

uses(RefreshDatabase::class);

final class InMemoryAsyncJobRepositoryForCoursesExportJob implements AsyncJobRepository
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
}

final class FakeFileStorageForCoursesExportJob implements FileStorage
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

final class FakeAuditLoggerForCoursesExportJob implements AuditLogger
{
    /** @var list<AuditEntry> */
    public array $logged = [];

    public function log(AuditEntry $entry): void
    {
        $this->logged[] = $entry;
    }
}

it('genera el csv de cursos y completa el trabajo asincrono', function (): void {
    app(CourseRepository::class)->save(Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('EXP-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso para exportar'),
    ));
    $jobs = new InMemoryAsyncJobRepositoryForCoursesExportJob;
    $asyncJobId = AsyncJobId::fromString((string) Str::uuid());
    $jobs->save(AsyncJob::request($asyncJobId, 'export.courses', 'user-1'));
    $fileStorage = new FakeFileStorageForCoursesExportJob;
    $auditLogger = new FakeAuditLoggerForCoursesExportJob;

    (new ExportCoursesJob($asyncJobId->value()))->handle(
        $jobs,
        app(CourseRepository::class),
        new ExportFileWriter($fileStorage),
        new CsvWriter,
        $auditLogger,
    );

    $completed = $jobs->findById($asyncJobId);
    expect($completed?->status())->toBe(AsyncJobStatus::Completed)
        ->and($completed?->result()['row_count'])->toBe(1)
        ->and($fileStorage->stored)->toHaveCount(1)
        ->and($auditLogger->logged[0]->action)->toBe('export.courses');

    $storedCsv = array_values($fileStorage->stored)[0];
    expect($storedCsv)->toContain('Curso para exportar');
});
