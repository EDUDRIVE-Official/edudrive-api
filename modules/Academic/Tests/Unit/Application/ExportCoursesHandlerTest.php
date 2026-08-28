<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\ExportCoursesCommand;
use Modules\Academic\Application\UseCases\ExportCoursesHandler;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Modules\Foundation\Application\Responses\ExportResponse;
use Modules\Foundation\Infrastructure\Export\CsvWriter;
use Modules\Foundation\Infrastructure\Export\ExportFileWriter;

final class FakeFileStorageForCoursesExport implements FileStorage
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

final class FakeAuditLoggerForCoursesExport implements AuditLogger
{
    /** @var list<AuditEntry> */
    public array $logged = [];

    public function log(AuditEntry $entry): void
    {
        $this->logged[] = $entry;
    }
}

it('exporta los cursos existentes a csv y devuelve una url de descarga', function (): void {
    app(CourseRepository::class)->save(Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('EXP-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso para exportar'),
    ));
    $fileStorage = new FakeFileStorageForCoursesExport;
    $auditLogger = new FakeAuditLoggerForCoursesExport;
    $handler = new ExportCoursesHandler(app(CourseRepository::class), new ExportFileWriter($fileStorage), new CsvWriter, $auditLogger);

    $response = $handler->handle(new ExportCoursesCommand);

    expect($response)->toBeInstanceOf(ExportResponse::class)
        ->and($response->rowCount)->toBe(1)
        ->and($response->format)->toBe('csv')
        ->and($fileStorage->stored)->toHaveCount(1);

    $storedCsv = array_values($fileStorage->stored)[0];
    expect($storedCsv)->toContain('Curso para exportar');
});

it('registra una entrada de auditoria por cada exportacion de cursos', function (): void {
    $fileStorage = new FakeFileStorageForCoursesExport;
    $auditLogger = new FakeAuditLoggerForCoursesExport;
    $handler = new ExportCoursesHandler(app(CourseRepository::class), new ExportFileWriter($fileStorage), new CsvWriter, $auditLogger);

    $handler->handle(new ExportCoursesCommand);

    expect($auditLogger->logged)->toHaveCount(1)
        ->and($auditLogger->logged[0]->action)->toBe('export.courses');
});
