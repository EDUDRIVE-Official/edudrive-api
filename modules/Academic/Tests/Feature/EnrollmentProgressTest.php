<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

uses(RefreshDatabase::class);

/**
 * Registers a real user row (satisfying the `academic_enrollments.user_id`
 * FK) WITHOUT changing the currently `Sanctum::actingAs()` user, so it can be
 * used as the owner of an "someone else's enrollment" fixture while a
 * different user remains authenticated for the request under test.
 */
function registerOtherUserIdForProgressFeature(): string
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

function activeEnrollmentForProgressFeature(?string $userId = null): Enrollment
{
    $course = createDraftCourseForPublishing('PRG-FEAT-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: $userId ?? registerOtherUserIdForProgressFeature(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

function firstLessonIdFor(Enrollment $enrollment): string
{
    $course = app(CourseRepository::class)->findById($enrollment->courseId());

    return (new CourseLessonCatalog(app(UnitContentRepository::class)))->lessonIdsFor($course)[0];
}

it('requires authentication to complete a lesson', function (): void {
    /** @var TestCase $this */
    $enrollment = activeEnrollmentForProgressFeature();

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/lessons/".Str::uuid().'/complete')
        ->assertUnauthorized();
});

it('requires authentication to view progress', function (): void {
    /** @var TestCase $this */
    $enrollment = activeEnrollmentForProgressFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/progress")
        ->assertUnauthorized();
});

it('completa una leccion propia', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $enrollment = activeEnrollmentForProgressFeature($userId);
    $lessonId = firstLessonIdFor($enrollment);

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/lessons/{$lessonId}/complete", [
        'time_spent_minutes' => 10,
    ])->assertOk()
        ->assertJsonPath('data.completed_lessons_count', 1)
        ->assertJsonPath('data.time_spent_minutes', 10)
        ->assertJsonPath('data.progress_percentage', 100);
});

it('rechaza completar una leccion ajena', function (): void {
    /** @var TestCase $this */
    actingAsUserId((string) Str::uuid());
    $enrollment = activeEnrollmentForProgressFeature();
    $lessonId = firstLessonIdFor($enrollment);

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/lessons/{$lessonId}/complete")
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});

it('rechaza completar una leccion inexistente en el curso', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $enrollment = activeEnrollmentForProgressFeature($userId);

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/lessons/".Str::uuid().'/complete')
        ->assertNotFound()
        ->assertJsonPath('code', 'LESSON_NOT_FOUND');
});

it('rechaza completar una leccion si el enrollment no esta activo', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $enrollment = activeEnrollmentForProgressFeature($userId);
    $enrollment->cancel();
    app(EnrollmentRepository::class)->save($enrollment);
    $lessonId = firstLessonIdFor($enrollment);

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/lessons/{$lessonId}/complete")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'INVALID_ENROLLMENT');
});

it('valida que time_spent_minutes no sea negativo', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $enrollment = activeEnrollmentForProgressFeature($userId);
    $lessonId = firstLessonIdFor($enrollment);

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/lessons/{$lessonId}/complete", [
        'time_spent_minutes' => -1,
    ])->assertUnprocessable();
});

it('consulta el progreso propio', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $enrollment = activeEnrollmentForProgressFeature($userId);

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/progress")
        ->assertOk()
        ->assertJsonPath('data.enrollment_id', $enrollment->id()->value());
});

it('rechaza consultar el progreso ajeno sin permiso ampliado', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $enrollment = activeEnrollmentForProgressFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/progress")
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});

it('permite consultar el progreso ajeno con enrollments.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $enrollment = activeEnrollmentForProgressFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/progress")
        ->assertOk()
        ->assertJsonPath('data.enrollment_id', $enrollment->id()->value());
});

it('responde 404 al consultar el progreso de un enrollment inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/academic/enrollments/'.Str::uuid().'/progress')
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});

it('permite consultar el progreso de un enrollment cancelado', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $enrollment = activeEnrollmentForProgressFeature($userId);
    $enrollment->cancel();
    app(EnrollmentRepository::class)->save($enrollment);

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/progress")
        ->assertOk()
        ->assertJsonPath('data.enrollment_id', $enrollment->id()->value());
});

it('consulta el estado de curriculo propio', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $enrollment = activeEnrollmentForProgressFeature($userId);

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/curriculum")
        ->assertOk()
        ->assertJsonPath('data.enrollment_id', $enrollment->id()->value())
        ->assertJsonPath('data.modules.0.unlocked', true);
});

it('rechaza consultar el curriculo ajeno sin permiso ampliado', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $enrollment = activeEnrollmentForProgressFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/curriculum")
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});

it('permite consultar el curriculo ajeno con enrollments.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $enrollment = activeEnrollmentForProgressFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/curriculum")
        ->assertOk()
        ->assertJsonPath('data.enrollment_id', $enrollment->id()->value());
});

it('responde 404 al consultar el curriculo de un enrollment inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/academic/enrollments/'.Str::uuid().'/curriculum')
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});

it('consulta las recomendaciones de aprendizaje propias', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $enrollment = activeEnrollmentForProgressFeature($userId);

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/recommendations")
        ->assertOk()
        ->assertJsonPath('data.enrollment_id', $enrollment->id()->value())
        ->assertJsonStructure(['data' => ['enrollment_id', 'next_lesson_id', 'weak_competencies', 'retryable_exams']]);
});

it('rechaza consultar recomendaciones ajenas sin permiso ampliado', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $enrollment = activeEnrollmentForProgressFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/recommendations")
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});

it('permite consultar recomendaciones ajenas con enrollments.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $enrollment = activeEnrollmentForProgressFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/recommendations")
        ->assertOk()
        ->assertJsonPath('data.enrollment_id', $enrollment->id()->value());
});

it('responde 404 al consultar recomendaciones de un enrollment inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/academic/enrollments/'.Str::uuid().'/recommendations')
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});
