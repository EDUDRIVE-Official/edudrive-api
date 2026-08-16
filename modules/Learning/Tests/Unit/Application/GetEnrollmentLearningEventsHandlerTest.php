<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Learning\Application\Queries\GetEnrollmentLearningEventsQuery;
use Modules\Learning\Application\Responses\LearningEventResponse;
use Modules\Learning\Application\UseCases\GetEnrollmentLearningEventsHandler;
use Modules\Learning\Domain\Entities\LearningEvent;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Learning\Domain\ValueObjects\LearningEventId;
use Modules\Learning\Domain\ValueObjects\LearningVerb;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function getEnrollmentLearningEventsHandler(): GetEnrollmentLearningEventsHandler
{
    return new GetEnrollmentLearningEventsHandler(
        app(EnrollmentRepository::class),
        app(LearningEventRepository::class),
    );
}

function persistedLearningEventsUserId(): string
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

function activeEnrollmentForLearningEvents(): Enrollment
{
    $course = createDraftCourseForPublishing('LRN-Q-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: persistedLearningEventsUserId(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

it('devuelve los eventos de aprendizaje al dueno del enrollment', function (): void {
    $enrollment = activeEnrollmentForLearningEvents();
    app(LearningEventRepository::class)->record(LearningEvent::create(
        id: LearningEventId::fromString((string) Str::uuid()),
        enrollmentId: $enrollment->id()->value(),
        userId: $enrollment->userId(),
        courseId: $enrollment->courseId()->value(),
        verb: LearningVerb::LessonCompleted,
        subjectId: (string) Str::uuid(),
        occurredAt: new DateTimeImmutable('now'),
        evidence: ['time_spent_minutes' => 3],
    ));

    $response = getEnrollmentLearningEventsHandler()->handle(new GetEnrollmentLearningEventsQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: $enrollment->userId(),
        canViewOthers: false,
    ));

    expect($response)->toBeInstanceOf(LearningEventResponse::class)
        ->and($response->enrollmentId)->toBe($enrollment->id()->value())
        ->and($response->events)->toHaveCount(1)
        ->and($response->events[0]['verb'])->toBe('lesson_completed');
});

it('rechaza consultar los eventos de un enrollment ajeno sin permiso ampliado', function (): void {
    $enrollment = activeEnrollmentForLearningEvents();

    expect(fn () => getEnrollmentLearningEventsHandler()->handle(new GetEnrollmentLearningEventsQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: persistedLearningEventsUserId(),
        canViewOthers: false,
    )))->toThrow(EnrollmentNotFound::class);
});

it('permite consultar los eventos de un enrollment ajeno con permiso ampliado', function (): void {
    $enrollment = activeEnrollmentForLearningEvents();

    $response = getEnrollmentLearningEventsHandler()->handle(new GetEnrollmentLearningEventsQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: persistedLearningEventsUserId(),
        canViewOthers: true,
    ));

    expect($response->enrollmentId)->toBe($enrollment->id()->value());
});

it('rechaza consultar los eventos de un enrollment inexistente', function (): void {
    expect(fn () => getEnrollmentLearningEventsHandler()->handle(new GetEnrollmentLearningEventsQuery(
        enrollmentId: (string) Str::uuid(),
        userId: (string) Str::uuid(),
        canViewOthers: true,
    )))->toThrow(EnrollmentNotFound::class);
});
