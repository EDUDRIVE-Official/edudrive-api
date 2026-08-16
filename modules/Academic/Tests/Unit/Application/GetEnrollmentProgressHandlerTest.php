<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Queries\GetEnrollmentProgressQuery;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Application\Services\EnrollmentProgressCalculator;
use Modules\Academic\Application\UseCases\GetEnrollmentProgressHandler;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function getEnrollmentProgressHandler(): GetEnrollmentProgressHandler
{
    return new GetEnrollmentProgressHandler(
        app(EnrollmentRepository::class),
        app(EnrollmentProgressRepository::class),
        new EnrollmentProgressCalculator(
            app(CourseRepository::class),
            new CourseLessonCatalog(app(UnitContentRepository::class)),
            app(ExamRepository::class),
            app(ExamAttemptRepository::class),
        ),
    );
}

function persistedTaskEightUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    app(UserRepository::class)->save($user);

    return $user->id();
}

function activeEnrollmentForProgressQuery(): Enrollment
{
    $course = createDraftCourseForPublishing('PRG-Q-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: persistedTaskEightUserId(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

it('devuelve el progreso al dueno del enrollment', function (): void {
    $enrollment = activeEnrollmentForProgressQuery();

    $response = getEnrollmentProgressHandler()->handle(new GetEnrollmentProgressQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: $enrollment->userId(),
        canViewOthers: false,
    ));

    expect($response)->toBeInstanceOf(EnrollmentProgressResponse::class)
        ->and($response->enrollmentId)->toBe($enrollment->id()->value());
});

it('rechaza consultar el progreso de un enrollment ajeno sin permiso ampliado', function (): void {
    $enrollment = activeEnrollmentForProgressQuery();

    expect(fn () => getEnrollmentProgressHandler()->handle(new GetEnrollmentProgressQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: persistedTaskEightUserId(),
        canViewOthers: false,
    )))->toThrow(EnrollmentNotFound::class);
});

it('permite consultar el progreso de un enrollment ajeno con permiso ampliado', function (): void {
    $enrollment = activeEnrollmentForProgressQuery();

    $response = getEnrollmentProgressHandler()->handle(new GetEnrollmentProgressQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: persistedTaskEightUserId(),
        canViewOthers: true,
    ));

    expect($response->enrollmentId)->toBe($enrollment->id()->value());
});

it('rechaza consultar el progreso de un enrollment inexistente', function (): void {
    expect(fn () => getEnrollmentProgressHandler()->handle(new GetEnrollmentProgressQuery(
        enrollmentId: (string) Str::uuid(),
        userId: persistedTaskEightUserId(),
        canViewOthers: true,
    )))->toThrow(EnrollmentNotFound::class);
});
