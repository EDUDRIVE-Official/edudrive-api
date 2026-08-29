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
use Modules\Analytics\Infrastructure\Jobs\GenerateAnalyticsReportJob;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Enums\AsyncJobStatus;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Domain\ValueObjects\CertificateId;
use Modules\Certification\Domain\ValueObjects\ValidationCode;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

final class InMemoryAsyncJobRepositoryForAnalyticsJob implements AsyncJobRepository
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

function persistedAnalyticsUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de analitica',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

it('genera el resumen de matriculas agrupado por estado', function (): void {
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('ANL-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso de analitica'),
    );
    app(CourseRepository::class)->save($course);
    app(EnrollmentRepository::class)->save(Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: persistedAnalyticsUserId(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    ));

    $jobs = new InMemoryAsyncJobRepositoryForAnalyticsJob;
    $asyncJobId = AsyncJobId::fromString((string) Str::uuid());
    $jobs->save(AsyncJob::request($asyncJobId, 'analytics.enrollments_summary', 'user-1'));

    (new GenerateAnalyticsReportJob($asyncJobId->value(), 'enrollments_summary'))->handle(
        $jobs,
        app(EnrollmentRepository::class),
        app(CertificateRepository::class),
        app(UserRepository::class),
    );

    $completed = $jobs->findById($asyncJobId);
    expect($completed?->status())->toBe(AsyncJobStatus::Completed)
        ->and($completed?->result()['total'])->toBe(1)
        ->and($completed?->result()['by_status'])->toBe(['active' => 1]);
});

it('genera el resumen de certificaciones agrupado por estado', function (): void {
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('ANL-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso para certificacion de analitica'),
    );
    app(CourseRepository::class)->save($course);

    app(CertificateRepository::class)->save(Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: persistedAnalyticsUserId(),
        courseId: $course->id()->value(),
        validationCode: ValidationCode::generate(),
    ));

    $jobs = new InMemoryAsyncJobRepositoryForAnalyticsJob;
    $asyncJobId = AsyncJobId::fromString((string) Str::uuid());
    $jobs->save(AsyncJob::request($asyncJobId, 'analytics.certifications_summary', 'user-1'));

    (new GenerateAnalyticsReportJob($asyncJobId->value(), 'certifications_summary'))->handle(
        $jobs,
        app(EnrollmentRepository::class),
        app(CertificateRepository::class),
        app(UserRepository::class),
    );

    $completed = $jobs->findById($asyncJobId);
    expect($completed?->result()['total'])->toBe(1)
        ->and($completed?->result()['by_status'])->toBe(['issued' => 1]);
});

it('genera el resumen de usuarios agrupado por estado', function (): void {
    persistedAnalyticsUserId();

    $jobs = new InMemoryAsyncJobRepositoryForAnalyticsJob;
    $asyncJobId = AsyncJobId::fromString((string) Str::uuid());
    $jobs->save(AsyncJob::request($asyncJobId, 'analytics.users_summary', 'user-1'));

    (new GenerateAnalyticsReportJob($asyncJobId->value(), 'users_summary'))->handle(
        $jobs,
        app(EnrollmentRepository::class),
        app(CertificateRepository::class),
        app(UserRepository::class),
    );

    $completed = $jobs->findById($asyncJobId);
    expect($completed?->result()['total'])->toBe(1);
});
