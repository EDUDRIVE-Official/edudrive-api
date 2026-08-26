<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Queries\GetEnrollmentLearningRecommendationsQuery;
use Modules\Academic\Application\Responses\LearningRecommendationsResponse;
use Modules\Academic\Application\Services\EnrollmentLearningRecommendationService;
use Modules\Academic\Application\UseCases\GetEnrollmentLearningRecommendationsHandler;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\CourseCurriculumUnlockCalculator;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function getEnrollmentLearningRecommendationsHandler(): GetEnrollmentLearningRecommendationsHandler
{
    return new GetEnrollmentLearningRecommendationsHandler(
        app(EnrollmentRepository::class),
        app(EnrollmentProgressRepository::class),
        new EnrollmentLearningRecommendationService(
            app(CourseRepository::class),
            new CourseLessonCatalog(app(UnitContentRepository::class)),
            new CourseCurriculumUnlockCalculator(app(UnitContentRepository::class)),
            app(ExamRepository::class),
            app(ExamAttemptRepository::class),
        ),
    );
}

function persistedRecommendationsQueryUserId(): string
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

function activeEnrollmentForRecommendationsQuery(): Enrollment
{
    $course = createDraftCourseForPublishing('PRG-RQ-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: persistedRecommendationsQueryUserId(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

it('devuelve las recomendaciones al dueno del enrollment', function (): void {
    $enrollment = activeEnrollmentForRecommendationsQuery();

    $response = getEnrollmentLearningRecommendationsHandler()->handle(new GetEnrollmentLearningRecommendationsQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: $enrollment->userId(),
        canViewOthers: false,
    ));

    expect($response)->toBeInstanceOf(LearningRecommendationsResponse::class)
        ->and($response->enrollmentId)->toBe($enrollment->id()->value());
});

it('rechaza consultar las recomendaciones de un enrollment ajeno sin permiso ampliado', function (): void {
    $enrollment = activeEnrollmentForRecommendationsQuery();

    expect(fn () => getEnrollmentLearningRecommendationsHandler()->handle(new GetEnrollmentLearningRecommendationsQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: persistedRecommendationsQueryUserId(),
        canViewOthers: false,
    )))->toThrow(EnrollmentNotFound::class);
});

it('permite consultar las recomendaciones de un enrollment ajeno con permiso ampliado', function (): void {
    $enrollment = activeEnrollmentForRecommendationsQuery();

    $response = getEnrollmentLearningRecommendationsHandler()->handle(new GetEnrollmentLearningRecommendationsQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: persistedRecommendationsQueryUserId(),
        canViewOthers: true,
    ));

    expect($response->enrollmentId)->toBe($enrollment->id()->value());
});

it('rechaza consultar las recomendaciones de un enrollment inexistente', function (): void {
    expect(fn () => getEnrollmentLearningRecommendationsHandler()->handle(new GetEnrollmentLearningRecommendationsQuery(
        enrollmentId: (string) Str::uuid(),
        userId: persistedRecommendationsQueryUserId(),
        canViewOthers: true,
    )))->toThrow(EnrollmentNotFound::class);
});
