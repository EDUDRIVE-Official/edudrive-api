<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\CompleteLessonCommand;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Exceptions\LessonNotFound;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Application\Services\EnrollmentProgressCalculator;
use Modules\Academic\Application\UseCases\CompleteLessonHandler;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Exceptions\InvalidEnrollment;
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

function completeLessonHandler(): CompleteLessonHandler
{
    return new CompleteLessonHandler(
        app(EnrollmentRepository::class),
        app(EnrollmentProgressRepository::class),
        app(CourseRepository::class),
        new CourseLessonCatalog(app(UnitContentRepository::class)),
        new EnrollmentProgressCalculator(
            app(CourseRepository::class),
            new CourseLessonCatalog(app(UnitContentRepository::class)),
            app(ExamRepository::class),
            app(ExamAttemptRepository::class),
        ),
    );
}

function persistedTaskSevenUserId(): string
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

function activeEnrollmentForLessonCompletion(): Enrollment
{
    $course = createDraftCourseForPublishing('PRG-CL-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: persistedTaskSevenUserId(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

it('completa una leccion del curso de la inscripcion', function (): void {
    $enrollment = activeEnrollmentForLessonCompletion();
    $course = app(CourseRepository::class)->findById($enrollment->courseId());
    $lessonId = (new CourseLessonCatalog(app(UnitContentRepository::class)))->lessonIdsFor($course)[0];

    $response = completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: $enrollment->id()->value(),
        lessonId: $lessonId,
        userId: $enrollment->userId(),
        timeSpentMinutes: 7,
    ));

    expect($response)->toBeInstanceOf(EnrollmentProgressResponse::class)
        ->and($response->completedLessonsCount)->toBe(1)
        ->and($response->timeSpentMinutes)->toBe(7);
});

it('rechaza completar una leccion de un enrollment inexistente o ajeno', function (): void {
    $enrollment = activeEnrollmentForLessonCompletion();
    $course = app(CourseRepository::class)->findById($enrollment->courseId());
    $lessonId = (new CourseLessonCatalog(app(UnitContentRepository::class)))->lessonIdsFor($course)[0];

    expect(fn () => completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: (string) Str::uuid(),
        lessonId: $lessonId,
        userId: $enrollment->userId(),
        timeSpentMinutes: null,
    )))->toThrow(EnrollmentNotFound::class);

    expect(fn () => completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: $enrollment->id()->value(),
        lessonId: $lessonId,
        userId: persistedTaskSevenUserId(),
        timeSpentMinutes: null,
    )))->toThrow(EnrollmentNotFound::class);
});

it('rechaza completar una leccion si el enrollment no esta activo', function (): void {
    $enrollment = activeEnrollmentForLessonCompletion();
    $enrollment->cancel();
    app(EnrollmentRepository::class)->save($enrollment);
    $course = app(CourseRepository::class)->findById($enrollment->courseId());
    $lessonId = (new CourseLessonCatalog(app(UnitContentRepository::class)))->lessonIdsFor($course)[0];

    expect(fn () => completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: $enrollment->id()->value(),
        lessonId: $lessonId,
        userId: $enrollment->userId(),
        timeSpentMinutes: null,
    )))->toThrow(InvalidEnrollment::class);
});

it('rechaza una leccion que no pertenece al curso de la inscripcion', function (): void {
    $enrollment = activeEnrollmentForLessonCompletion();

    expect(fn () => completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: $enrollment->id()->value(),
        lessonId: (string) Str::uuid(),
        userId: $enrollment->userId(),
        timeSpentMinutes: null,
    )))->toThrow(LessonNotFound::class);
});
