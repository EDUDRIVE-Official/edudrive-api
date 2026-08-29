<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
use Modules\Academic\Infrastructure\Jobs\ExportEnrollmentsJob;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Enums\AsyncJobStatus;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Modules\Foundation\Infrastructure\Export\CsvWriter;
use Modules\Foundation\Infrastructure\Export\ExportFileWriter;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function persistedExportEnrollmentJobUserId(): string
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

final class InMemoryAsyncJobRepositoryForEnrollmentsExportJob implements AsyncJobRepository
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

final class FakeFileStorageForEnrollmentsExportJob implements FileStorage
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

final class FakeAuditLoggerForEnrollmentsExportJob implements AuditLogger
{
    /** @var list<AuditEntry> */
    public array $logged = [];

    public function log(AuditEntry $entry): void
    {
        $this->logged[] = $entry;
    }
}

it('genera el csv de enrollments y completa el trabajo asincrono', function (): void {
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('EXP-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso para exportar enrollments'),
    );
    app(CourseRepository::class)->save($course);
    app(EnrollmentRepository::class)->save(Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: persistedExportEnrollmentJobUserId(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    ));

    $jobs = new InMemoryAsyncJobRepositoryForEnrollmentsExportJob;
    $asyncJobId = AsyncJobId::fromString((string) Str::uuid());
    $jobs->save(AsyncJob::request($asyncJobId, 'export.enrollments', 'user-1'));
    $fileStorage = new FakeFileStorageForEnrollmentsExportJob;
    $auditLogger = new FakeAuditLoggerForEnrollmentsExportJob;

    (new ExportEnrollmentsJob($asyncJobId->value()))->handle(
        $jobs,
        app(EnrollmentRepository::class),
        new ExportFileWriter($fileStorage),
        new CsvWriter,
        $auditLogger,
    );

    $completed = $jobs->findById($asyncJobId);
    expect($completed?->status())->toBe(AsyncJobStatus::Completed)
        ->and($completed?->result()['row_count'])->toBe(1)
        ->and($completed?->result()['storage_path'])->toStartWith('exports/enrollments/')
        ->and($fileStorage->stored)->toHaveCount(1)
        ->and($auditLogger->logged[0]->action)->toBe('export.enrollments');

    $storedCsv = array_values($fileStorage->stored)[0];
    expect($storedCsv)->toContain($course->id()->value());
});
