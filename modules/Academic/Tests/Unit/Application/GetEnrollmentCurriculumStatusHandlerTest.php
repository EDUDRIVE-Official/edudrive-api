<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Queries\GetEnrollmentCurriculumStatusQuery;
use Modules\Academic\Application\Responses\CurriculumUnlockResponse;
use Modules\Academic\Application\UseCases\GetEnrollmentCurriculumStatusHandler;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\CourseCurriculumUnlockCalculator;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function getEnrollmentCurriculumStatusHandler(): GetEnrollmentCurriculumStatusHandler
{
    return new GetEnrollmentCurriculumStatusHandler(
        app(EnrollmentRepository::class),
        app(EnrollmentProgressRepository::class),
        app(CourseRepository::class),
        new CourseCurriculumUnlockCalculator(app(UnitContentRepository::class)),
    );
}

function persistedTaskCurriculumUserId(): string
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

function activeEnrollmentForCurriculumStatus(): Enrollment
{
    $course = createDraftCourseForPublishing('PRG-CURR-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: persistedTaskCurriculumUserId(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

it('devuelve el estado de curriculo al dueno del enrollment', function (): void {
    $enrollment = activeEnrollmentForCurriculumStatus();

    $response = getEnrollmentCurriculumStatusHandler()->handle(new GetEnrollmentCurriculumStatusQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: $enrollment->userId(),
        canViewOthers: false,
    ));

    expect($response)->toBeInstanceOf(CurriculumUnlockResponse::class)
        ->and($response->enrollmentId)->toBe($enrollment->id()->value())
        ->and($response->modules)->toHaveCount(1)
        ->and($response->modules[0]['units'][0]['unlocked'])->toBeTrue();
});

it('rechaza consultar el curriculo de un enrollment ajeno sin permiso ampliado', function (): void {
    $enrollment = activeEnrollmentForCurriculumStatus();

    expect(fn () => getEnrollmentCurriculumStatusHandler()->handle(new GetEnrollmentCurriculumStatusQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: persistedTaskCurriculumUserId(),
        canViewOthers: false,
    )))->toThrow(EnrollmentNotFound::class);
});

it('permite consultar el curriculo de un enrollment ajeno con permiso ampliado', function (): void {
    $enrollment = activeEnrollmentForCurriculumStatus();

    $response = getEnrollmentCurriculumStatusHandler()->handle(new GetEnrollmentCurriculumStatusQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: persistedTaskCurriculumUserId(),
        canViewOthers: true,
    ));

    expect($response->enrollmentId)->toBe($enrollment->id()->value());
});

it('rechaza consultar el curriculo de un enrollment inexistente', function (): void {
    expect(fn () => getEnrollmentCurriculumStatusHandler()->handle(new GetEnrollmentCurriculumStatusQuery(
        enrollmentId: (string) Str::uuid(),
        userId: (string) Str::uuid(),
        canViewOthers: true,
    )))->toThrow(EnrollmentNotFound::class);
});
