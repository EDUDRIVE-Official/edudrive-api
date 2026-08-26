<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Learning\Domain\Entities\LearningEvent;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Learning\Domain\ValueObjects\LearningEventId;
use Modules\Learning\Domain\ValueObjects\LearningVerb;
use Tests\TestCase;

uses(RefreshDatabase::class);

function registerOtherUserIdForLearningEventsFeature(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    app(UserRepository::class)->save($user);

    return $user->id();
}

function activeEnrollmentForLearningEventsFeature(?string $userId = null): Enrollment
{
    $course = createDraftCourseForPublishing('LRN-F-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: $userId ?? registerOtherUserIdForLearningEventsFeature(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

it('consulta los eventos de aprendizaje propios', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $enrollment = activeEnrollmentForLearningEventsFeature($userId);
    app(LearningEventRepository::class)->record(LearningEvent::create(
        id: LearningEventId::fromString((string) Str::uuid()),
        enrollmentId: $enrollment->id()->value(),
        userId: $userId,
        courseId: $enrollment->courseId()->value(),
        verb: LearningVerb::LessonCompleted,
        subjectId: (string) Str::uuid(),
        occurredAt: new DateTimeImmutable('now'),
        evidence: [],
    ));

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/learning-events")
        ->assertOk()
        ->assertJsonPath('data.enrollment_id', $enrollment->id()->value())
        ->assertJsonPath('data.events.0.verb', 'lesson_completed');
});

it('rechaza consultar eventos de aprendizaje ajenos sin permiso ampliado', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $enrollment = activeEnrollmentForLearningEventsFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/learning-events")
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});

it('permite consultar eventos de aprendizaje ajenos con enrollments.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $enrollment = activeEnrollmentForLearningEventsFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/learning-events")
        ->assertOk()
        ->assertJsonPath('data.enrollment_id', $enrollment->id()->value())
        ->assertJsonPath('data.events', []);
});

it('responde 404 al consultar eventos de aprendizaje de un enrollment inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/academic/enrollments/'.Str::uuid().'/learning-events')
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});
