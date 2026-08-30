<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

function persistedGroupFeatureCourse(): Course
{
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('COURSE-GRP-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso de prueba para grupos'),
    );
    app(CourseRepository::class)->save($course);

    return $course;
}

function persistedGroupFeatureTeacherId(): string
{
    $teacher = User::register(
        id: (string) Str::uuid(),
        name: 'Docente de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($teacher);

    return $teacher->id();
}

it('crea un grupo con el permiso groups.manage', function (): void {
    /** @var TestCase $this */
    $course = persistedGroupFeatureCourse();
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/groups', [
        'course_id' => $course->id()->value(),
        'name' => 'Generación 2026-I',
        'starts_at' => '2026-01-15',
        'ends_at' => '2026-06-15',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Generación 2026-I')
        ->assertJsonPath('data.course_id', $course->id()->value());
});

it('rechaza crear un grupo con un curso inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/groups', [
        'course_id' => (string) Str::uuid(),
        'name' => 'Grupo A',
        'starts_at' => '2026-01-15',
        'ends_at' => '2026-06-15',
    ])
        ->assertStatus(404)
        ->assertJsonPath('code', 'COURSE_NOT_FOUND');
});

it('rechaza un periodo lectivo invalido', function (): void {
    /** @var TestCase $this */
    $course = persistedGroupFeatureCourse();
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/groups', [
        'course_id' => $course->id()->value(),
        'name' => 'Grupo A',
        'starts_at' => '2026-06-15',
        'ends_at' => '2026-01-15',
    ])->assertJsonValidationErrors('ends_at');
});

it('rechaza crear un grupo sin el permiso groups.manage', function (): void {
    /** @var TestCase $this */
    $course = persistedGroupFeatureCourse();
    actingAsRole(Role::Student);

    $this->postJson('/api/v1/academic/groups', [
        'course_id' => $course->id()->value(),
        'name' => 'Grupo A',
        'starts_at' => '2026-01-15',
        'ends_at' => '2026-06-15',
    ])->assertForbidden();
});

it('lista los grupos con el permiso groups.view, opcionalmente filtrados por curso', function (): void {
    /** @var TestCase $this */
    $courseA = persistedGroupFeatureCourse();
    $courseB = persistedGroupFeatureCourse();
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/groups', [
        'course_id' => $courseA->id()->value(),
        'name' => 'Grupo A',
        'starts_at' => '2026-01-15',
        'ends_at' => '2026-06-15',
    ])->assertCreated();

    $this->postJson('/api/v1/academic/groups', [
        'course_id' => $courseB->id()->value(),
        'name' => 'Grupo B',
        'starts_at' => '2026-01-15',
        'ends_at' => '2026-06-15',
    ])->assertCreated();

    $this->getJson('/api/v1/academic/groups?course_id='.$courseA->id()->value())
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Grupo A');
});

it('reasigna el docente de un grupo con el permiso groups.manage', function (): void {
    /** @var TestCase $this */
    $course = persistedGroupFeatureCourse();
    $teacherId = persistedGroupFeatureTeacherId();
    actingAsRole(Role::SuperAdmin);

    $groupId = $this->postJson('/api/v1/academic/groups', [
        'course_id' => $course->id()->value(),
        'name' => 'Grupo A',
        'starts_at' => '2026-01-15',
        'ends_at' => '2026-06-15',
    ])->json('data.id');

    $this->postJson("/api/v1/academic/groups/{$groupId}/assign-teacher", [
        'teacher_id' => $teacherId,
    ])
        ->assertOk()
        ->assertJsonPath('data.teacher_id', $teacherId);
});

it('rechaza reasignar el docente de un grupo inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/groups/'.Str::uuid().'/assign-teacher', [
        'teacher_id' => persistedGroupFeatureTeacherId(),
    ])
        ->assertStatus(404)
        ->assertJsonPath('code', 'GROUP_NOT_FOUND');
});

it('requiere autenticacion para todos los endpoints de grupos', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/academic/groups')->assertUnauthorized();
    $this->postJson('/api/v1/academic/groups', [])->assertUnauthorized();
    $this->postJson('/api/v1/academic/groups/'.Str::uuid().'/assign-teacher', [])->assertUnauthorized();
});
