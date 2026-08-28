<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\ExportEnrollmentsCommand;
use Modules\Academic\Application\UseCases\ExportEnrollmentsHandler;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Modules\Foundation\Application\Responses\ExportResponse;
use Modules\Foundation\Infrastructure\Export\CsvWriter;
use Modules\Foundation\Infrastructure\Export\ExportFileWriter;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

function persistedExportEnrollmentUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario para exportar enrollments',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

final class FakeFileStorageForEnrollmentsExport implements FileStorage
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

final class FakeAuditLoggerForEnrollmentsExport implements AuditLogger
{
    /** @var list<AuditEntry> */
    public array $logged = [];

    public function log(AuditEntry $entry): void
    {
        $this->logged[] = $entry;
    }
}

it('exporta los enrollments existentes a csv y devuelve una url de descarga', function (): void {
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('EXP-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso para exportar enrollments'),
    );
    app(CourseRepository::class)->save($course);
    app(EnrollmentRepository::class)->save(Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: persistedExportEnrollmentUserId(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    ));

    $fileStorage = new FakeFileStorageForEnrollmentsExport;
    $auditLogger = new FakeAuditLoggerForEnrollmentsExport;
    $handler = new ExportEnrollmentsHandler(app(EnrollmentRepository::class), new ExportFileWriter($fileStorage), new CsvWriter, $auditLogger);

    $response = $handler->handle(new ExportEnrollmentsCommand);

    expect($response)->toBeInstanceOf(ExportResponse::class)
        ->and($response->rowCount)->toBe(1)
        ->and($response->format)->toBe('csv')
        ->and($fileStorage->stored)->toHaveCount(1);

    $storedCsv = array_values($fileStorage->stored)[0];
    expect($storedCsv)->toContain($course->id()->value());
});

it('registra una entrada de auditoria por cada exportacion de enrollments', function (): void {
    $fileStorage = new FakeFileStorageForEnrollmentsExport;
    $auditLogger = new FakeAuditLoggerForEnrollmentsExport;
    $handler = new ExportEnrollmentsHandler(app(EnrollmentRepository::class), new ExportFileWriter($fileStorage), new CsvWriter, $auditLogger);

    $handler->handle(new ExportEnrollmentsCommand);

    expect($auditLogger->logged)->toHaveCount(1)
        ->and($auditLogger->logged[0]->action)->toBe('export.enrollments');
});
