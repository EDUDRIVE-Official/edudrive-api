<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Learning\Domain\Entities\LearningEvent;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Learning\Domain\ValueObjects\LearningEventId;
use Modules\Learning\Domain\ValueObjects\LearningVerb;

uses(RefreshDatabase::class);

function persistedUserForLearningEvents(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de aprendizaje',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function persistedEnrollmentForLearningEvents(): Enrollment
{
    $course = createDraftCourseForPublishing('LRN-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: persistedUserForLearningEvents(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

it('registra y recupera eventos de aprendizaje ordenados del mas reciente al mas antiguo', function (): void {
    $enrollment = persistedEnrollmentForLearningEvents();
    $repository = app(LearningEventRepository::class);

    $repository->record(LearningEvent::create(
        id: LearningEventId::fromString((string) Str::uuid()),
        enrollmentId: $enrollment->id()->value(),
        userId: $enrollment->userId(),
        courseId: $enrollment->courseId()->value(),
        verb: LearningVerb::LessonCompleted,
        subjectId: (string) Str::uuid(),
        occurredAt: new DateTimeImmutable('2026-08-16T09:00:00+00:00'),
        evidence: ['time_spent_minutes' => 5],
    ));
    $repository->record(LearningEvent::create(
        id: LearningEventId::fromString((string) Str::uuid()),
        enrollmentId: $enrollment->id()->value(),
        userId: $enrollment->userId(),
        courseId: $enrollment->courseId()->value(),
        verb: LearningVerb::ExamAttemptSubmitted,
        subjectId: (string) Str::uuid(),
        occurredAt: new DateTimeImmutable('2026-08-16T10:00:00+00:00'),
        evidence: ['score' => 8, 'total_points' => 10, 'percentage' => 80, 'passed' => true],
    ));

    $events = $repository->findByEnrollmentId($enrollment->id()->value());

    expect($events)->toHaveCount(2)
        ->and($events[0]->verb())->toBe(LearningVerb::ExamAttemptSubmitted)
        ->and($events[0]->evidence())->toBe(['score' => 8, 'total_points' => 10, 'percentage' => 80, 'passed' => true])
        ->and($events[1]->verb())->toBe(LearningVerb::LessonCompleted);
});

it('no devuelve eventos de otro enrollment', function (): void {
    $enrollment = persistedEnrollmentForLearningEvents();
    $otherEnrollment = persistedEnrollmentForLearningEvents();
    $repository = app(LearningEventRepository::class);

    $repository->record(LearningEvent::create(
        id: LearningEventId::fromString((string) Str::uuid()),
        enrollmentId: $otherEnrollment->id()->value(),
        userId: $otherEnrollment->userId(),
        courseId: $otherEnrollment->courseId()->value(),
        verb: LearningVerb::LessonCompleted,
        subjectId: (string) Str::uuid(),
        occurredAt: new DateTimeImmutable('now'),
        evidence: [],
    ));

    expect($repository->findByEnrollmentId($enrollment->id()->value()))->toBeEmpty();
});
