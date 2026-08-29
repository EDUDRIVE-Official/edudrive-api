<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedEnrollmentUserId(): string
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

function persistedEnrollmentForFeature(
    string $status = 'pending',
    string $source = 'individual',
    ?string $courseId = null,
    ?string $userId = null,
    ?string $organizationId = null,
): Enrollment {
    $courseId ??= createDraftCourseForPublishing('ENR-'.strtoupper((string) Str::random(4)))->id()->value();

    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString($courseId),
        userId: $userId ?? persistedEnrollmentUserId(),
        organizationId: $organizationId === null ? null : OrganizationId::fromString($organizationId),
        status: EnrollmentStatus::from($status),
        source: EnrollmentSource::from($source),
    );

    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

it('requires authentication to list enrollments', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/academic/enrollments')->assertUnauthorized();
});

it('forbids listing enrollments to students', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);

    $this->getJson('/api/v1/academic/enrollments')->assertForbidden();
});

it('lista enrollments con permission enrollments.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    persistedEnrollmentForFeature();

    $this->getJson('/api/v1/academic/enrollments')
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('muestra un enrollment especifico', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $enrollment = persistedEnrollmentForFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $enrollment->id()->value());
});

it('responde 404 al mostrar un enrollment inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/academic/enrollments/'.Str::uuid())
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});

it('lista enrollments filtrados por source y status', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    persistedEnrollmentForFeature(status: 'active', source: 'bulk');
    persistedEnrollmentForFeature(status: 'pending', source: 'individual');

    $this->getJson('/api/v1/academic/enrollments?source=bulk&status=active')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.source', 'bulk')
        ->assertJsonPath('data.0.status', 'active');
});

it('crea una inscripcion individual', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);
    $course = createDraftCourseForPublishing('ENR-IND-01');

    $this->postJson('/api/v1/academic/enrollments', [
        'course_id' => $course->id()->value(),
        'user_id' => persistedEnrollmentUserId(),
        'status' => 'pending',
    ])->assertCreated()
        ->assertJsonPath('data.source', 'individual')
        ->assertJsonPath('data.status', 'pending');
});

it('crea una inscripcion bulk', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);
    $course = createDraftCourseForPublishing('ENR-BULK-01');

    $this->postJson('/api/v1/academic/enrollments/bulk', [
        'course_id' => $course->id()->value(),
        'user_ids' => [persistedEnrollmentUserId(), persistedEnrollmentUserId()],
        'status' => 'active',
    ])->assertCreated()
        ->assertJsonPath('data.total', 2)
        ->assertJsonPath('data.created', 2)
        ->assertJsonPath('data.failed', 0);
});

it('crea una inscripcion institucional', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);
    $course = createDraftCourseForPublishing('ENR-INS-01');

    $this->postJson('/api/v1/academic/enrollments/institutional', [
        'course_id' => $course->id()->value(),
        'user_id' => persistedEnrollmentUserId(),
        'organization_id' => (string) Str::uuid(),
        'status' => 'active',
    ])->assertCreated()
        ->assertJsonPath('data.source', 'institutional');
});

it('devuelve la inscripcion institucional existente en vez de fallar ante un reintento', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);
    $course = createDraftCourseForPublishing('ENR-INS-02');
    $userId = persistedEnrollmentUserId();
    $organizationId = (string) Str::uuid();

    $first = $this->postJson('/api/v1/academic/enrollments/institutional', [
        'course_id' => $course->id()->value(),
        'user_id' => $userId,
        'organization_id' => $organizationId,
        'status' => 'active',
    ])->assertCreated();

    $second = $this->postJson('/api/v1/academic/enrollments/institutional', [
        'course_id' => $course->id()->value(),
        'user_id' => $userId,
        'organization_id' => $organizationId,
        'status' => 'active',
    ])->assertCreated();

    expect($second->json('data.id'))->toBe($first->json('data.id'));
});

it('valida payloads invalidos en create endpoints', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    $this->postJson('/api/v1/academic/enrollments', [
        'course_id' => 'bad-id',
        'user_id' => 'bad-id',
        'status' => 'weird',
    ])->assertUnprocessable();

    $this->postJson('/api/v1/academic/enrollments/bulk', [
        'course_id' => 'bad-id',
        'user_ids' => [],
        'status' => 'weird',
    ])->assertUnprocessable();

    $this->postJson('/api/v1/academic/enrollments/institutional', [
        'course_id' => 'bad-id',
        'user_id' => 'bad-id',
        'status' => 'weird',
    ])->assertUnprocessable();
});

it('rechaza crear un enrollment para un curso inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    $this->postJson('/api/v1/academic/enrollments', [
        'course_id' => (string) Str::uuid(),
        'user_id' => (string) Str::uuid(),
        'status' => 'pending',
    ])->assertNotFound()
        ->assertJsonPath('code', 'COURSE_NOT_FOUND');
});

it('devuelve el enrollment existente en vez de fallar ante un reintento (idempotencia)', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);
    $course = createDraftCourseForPublishing('ENR-DUP-01');
    $userId = persistedEnrollmentUserId();

    $first = $this->postJson('/api/v1/academic/enrollments', [
        'course_id' => $course->id()->value(),
        'user_id' => $userId,
        'status' => 'pending',
    ])->assertCreated();

    $second = $this->postJson('/api/v1/academic/enrollments', [
        'course_id' => $course->id()->value(),
        'user_id' => $userId,
        'status' => 'pending',
    ])->assertCreated();

    expect($second->json('data.id'))->toBe($first->json('data.id'));
});

it('forbids creating enrollments to teachers', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $course = createDraftCourseForPublishing('ENR-FORB-01');

    $this->postJson('/api/v1/academic/enrollments', [
        'course_id' => $course->id()->value(),
        'user_id' => (string) Str::uuid(),
        'status' => 'pending',
    ])->assertForbidden();
});

it('activa una inscripcion pendiente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);
    $enrollment = persistedEnrollmentForFeature(status: 'pending');

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/activate")
        ->assertOk()
        ->assertJsonPath('data.status', 'active');
});

it('completa una inscripcion activa', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);
    $enrollment = persistedEnrollmentForFeature(status: 'active');

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');
});

it('cancela una inscripcion activa', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);
    $enrollment = persistedEnrollmentForFeature(status: 'active');

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', 'canceled');
});

it('rechaza completar una inscripcion cancelada', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);
    $enrollment = persistedEnrollmentForFeature(status: 'canceled');

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/complete")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'INVALID_ENROLLMENT');
});

it('responde 404 al aplicar transiciones sobre un enrollment inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);
    $enrollmentId = (string) Str::uuid();

    $this->postJson("/api/v1/academic/enrollments/{$enrollmentId}/activate")
        ->assertNotFound()->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
    $this->postJson("/api/v1/academic/enrollments/{$enrollmentId}/complete")
        ->assertNotFound()->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
    $this->postJson("/api/v1/academic/enrollments/{$enrollmentId}/cancel")
        ->assertNotFound()->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});
