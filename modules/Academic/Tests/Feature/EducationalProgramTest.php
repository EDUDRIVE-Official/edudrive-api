<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

function actingAsProgramRole(Role $role): void
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de programas',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);
    app(RoleAssignmentRepository::class)->save(RoleAssignment::assign(
        id: (string) Str::uuid(),
        userId: $user->id(),
        role: $role,
        organizationId: null,
    ));
    Sanctum::actingAs(UserModel::query()->findOrFail($user->id()));
}

/** @return array<string, mixed> */
function validProgramPayload(string $code = 'MOTO-LEARNER-01'): array
{
    return [
        'code' => $code,
        'title' => 'Aprendices de motocicleta',
        'description' => 'Programa regional inicial.',
        'min_age' => 16,
        'max_age' => 18,
        'license_stages' => ['learner'],
        'contexts' => ['general'],
        'vehicle_types' => ['motorcycle'],
    ];
}

/** @return array{id: string, code: string} */
function createPublishedCourseForProgram(TestCase $test, string $code): array
{
    $created = $test->postJson('/api/v1/academic/courses', [
        'code' => $code,
        'title' => "Curso {$code}",
    ])->assertCreated();

    $id = (string) $created->json('data.id');
    $course = app(CourseRepository::class)->findById(CourseId::fromString($id));

    if ($course === null) {
        throw new RuntimeException("No se encontro el curso {$id} recien creado.");
    }

    addMinimalCurriculum($course);
    preserveCourseCurriculumInMemory($course);

    $test->postJson("/api/v1/academic/courses/{$id}/publish")
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    return ['id' => $id, 'code' => $code];
}

it('administra el ciclo completo de un programa con cursos ordenados', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $firstCourse = createPublishedCourseForProgram($this, 'PROG-C01');
    $secondCourse = createPublishedCourseForProgram($this, 'PROG-C02');

    $created = $this->postJson('/api/v1/academic/programs', validProgramPayload())
        ->assertCreated()
        ->assertJsonPath('data.code', 'MOTO-LEARNER-01')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.audience.min_age', 16)
        ->assertJsonPath('data.courses', []);

    $programId = (string) $created->json('data.id');

    $this->patchJson("/api/v1/academic/programs/{$programId}/audience", [
        'min_age' => 17,
        'max_age' => 21,
        'license_stages' => ['learner', 'licensed'],
        'contexts' => ['institutional'],
        'vehicle_types' => ['motorcycle', 'automobile'],
    ])->assertOk()
        ->assertJsonPath('data.audience.min_age', 17)
        ->assertJsonPath('data.audience.contexts.0', 'institutional');

    $this->putJson("/api/v1/academic/programs/{$programId}/courses", [
        'course_ids' => [$secondCourse['id'], $firstCourse['id']],
    ])->assertOk()
        ->assertJsonPath('data.courses.0.course_id', $secondCourse['id'])
        ->assertJsonPath('data.courses.0.position', 1)
        ->assertJsonPath('data.courses.1.course_id', $firstCourse['id'])
        ->assertJsonPath('data.courses.1.position', 2);

    $this->postJson("/api/v1/academic/programs/{$programId}/publish")
        ->assertOk()
        ->assertJsonPath('data.status', 'published')
        ->assertJsonPath('data.courses.0.course_id', $secondCourse['id'])
        ->assertJsonPath('data.courses.1.course_id', $firstCourse['id'])
        ->assertJsonPath('data.published_at', fn (mixed $value): bool => is_string($value));

    $this->getJson('/api/v1/academic/programs')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $programId)
        ->assertJsonPath('data.0.courses.0.course_id', $secondCourse['id'])
        ->assertJsonPath('data.0.courses.0.position', 1)
        ->assertJsonPath('data.0.courses.1.course_id', $firstCourse['id'])
        ->assertJsonPath('data.0.courses.1.position', 2);

    $this->postJson("/api/v1/academic/programs/{$programId}/archive")
        ->assertOk()
        ->assertJsonPath('data.status', 'archived')
        ->assertJsonPath('data.archived_at', fn (mixed $value): bool => is_string($value));

    $this->patchJson("/api/v1/academic/programs/{$programId}/audience", [
        'license_stages' => [],
        'contexts' => [],
        'vehicle_types' => [],
    ])->assertUnprocessable()
        ->assertJsonPath('code', 'ARCHIVED_PROGRAM_CANNOT_BE_MODIFIED');
});

it('valida vocabularios y edades del programa', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $payload = validProgramPayload('PROGRAM-INVALID');
    $payload['min_age'] = -1;
    $payload['max_age'] = 'adult';
    $payload['license_stages'] = ['novice'];
    $payload['contexts'] = ['private'];
    $payload['vehicle_types'] = ['truck'];

    $this->postJson('/api/v1/academic/programs', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'min_age',
            'max_age',
            'license_stages.0',
            'contexts.0',
            'vehicle_types.0',
        ]);
});

it('rechaza un rango etario invertido mediante la invariante del dominio', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $payload = validProgramPayload('PROGRAM-AGE-RANGE');
    $payload['min_age'] = 19;
    $payload['max_age'] = 18;

    $this->postJson('/api/v1/academic/programs', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('code', 'INVALID_PROGRAM_AGE_RANGE');
});

it('alinea create y patch con el limite smallint de edades', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $maximumPayload = validProgramPayload('PROGRAM-MAXIMUM-AGE');
    $maximumPayload['min_age'] = 32767;
    $maximumPayload['max_age'] = 32767;

    $created = $this->postJson('/api/v1/academic/programs', $maximumPayload)
        ->assertCreated()
        ->assertJsonPath('data.audience.min_age', 32767)
        ->assertJsonPath('data.audience.max_age', 32767);
    $programId = (string) $created->json('data.id');

    $invalidPayload = validProgramPayload('PROGRAM-OVERFLOW-AGE');
    $invalidPayload['max_age'] = 32768;

    $this->postJson('/api/v1/academic/programs', $invalidPayload)
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonValidationErrors(['max_age']);

    $this->patchJson("/api/v1/academic/programs/{$programId}/audience", [
        'min_age' => 32768,
    ])->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonValidationErrors(['min_age']);
});

it('preserva los criterios omitidos al actualizar parcialmente la audiencia', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $created = $this->postJson('/api/v1/academic/programs', validProgramPayload('PROGRAM-PATCH-AUDIENCE'))
        ->assertCreated();
    $programId = (string) $created->json('data.id');

    $this->patchJson("/api/v1/academic/programs/{$programId}/audience", [
        'contexts' => ['corporate'],
    ])->assertOk()
        ->assertJsonPath('data.audience.min_age', 16)
        ->assertJsonPath('data.audience.max_age', 18)
        ->assertJsonPath('data.audience.license_stages', ['learner'])
        ->assertJsonPath('data.audience.contexts', ['corporate'])
        ->assertJsonPath('data.audience.vehicle_types', ['motorcycle']);

    $this->patchJson("/api/v1/academic/programs/{$programId}/audience", [
        'min_age' => null,
        'license_stages' => [],
        'vehicle_types' => [],
    ])->assertOk()
        ->assertJsonPath('data.audience.min_age', null)
        ->assertJsonPath('data.audience.max_age', 18)
        ->assertJsonPath('data.audience.license_stages', [])
        ->assertJsonPath('data.audience.contexts', ['corporate'])
        ->assertJsonPath('data.audience.vehicle_types', []);
});

it('protege la consulta con autenticación y separa permisos de lectura y gestión', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/academic/programs')->assertUnauthorized();

    actingAsProgramRole(Role::Teacher);

    $this->getJson('/api/v1/academic/programs')->assertOk();
    $this->postJson('/api/v1/academic/programs', validProgramPayload('PROGRAM-FORBIDDEN'))
        ->assertForbidden();
});

it('rechaza códigos de programa duplicados', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $this->postJson('/api/v1/academic/programs', validProgramPayload('PROGRAM-DUPLICATE'))
        ->assertCreated();
    $this->postJson('/api/v1/academic/programs', validProgramPayload('program-duplicate'))
        ->assertConflict()
        ->assertJsonPath('code', 'PROGRAM_CODE_ALREADY_EXISTS');
});

it('rechaza cursos inexistentes y secuencias duplicadas', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $created = $this->postJson('/api/v1/academic/programs', validProgramPayload('PROGRAM-COURSES'))
        ->assertCreated();
    $programId = (string) $created->json('data.id');
    $unknownCourseId = (string) Str::uuid();

    $this->putJson("/api/v1/academic/programs/{$programId}/courses", [
        'course_ids' => [$unknownCourseId],
    ])->assertNotFound()
        ->assertJsonPath('code', 'PROGRAM_COURSE_NOT_FOUND');

    $this->putJson("/api/v1/academic/programs/{$programId}/courses", [
        'course_ids' => [$unknownCourseId, strtoupper($unknownCourseId)],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['course_ids.0']);
});

it('rechaza publicar un programa con un curso no publicado', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = $this->postJson('/api/v1/academic/courses', [
        'code' => 'PROG-DRAFT-C01',
        'title' => 'Curso borrador del programa',
    ])->assertCreated();
    $courseId = (string) $course->json('data.id');

    $created = $this->postJson('/api/v1/academic/programs', validProgramPayload('PROGRAM-DRAFT-COURSE'))
        ->assertCreated();
    $programId = (string) $created->json('data.id');

    $this->putJson("/api/v1/academic/programs/{$programId}/courses", [
        'course_ids' => [$courseId],
    ])->assertOk();

    $this->postJson("/api/v1/academic/programs/{$programId}/publish")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'PROGRAM_COURSE_NOT_PUBLISHED');
});

it('mantiene valida y atomica la secuencia al reemplazar cursos de un programa publicado', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $firstCourse = createPublishedCourseForProgram($this, 'PROG-PUBLISHED-C01');
    $secondCourse = createPublishedCourseForProgram($this, 'PROG-PUBLISHED-C02');
    $draftCourse = $this->postJson('/api/v1/academic/courses', [
        'code' => 'PROG-PUBLISHED-DRAFT',
        'title' => 'Curso borrador para reemplazo',
    ])->assertCreated();
    $draftCourseId = (string) $draftCourse->json('data.id');

    $created = $this->postJson('/api/v1/academic/programs', validProgramPayload('PROGRAM-PUBLISHED-REPLACE'))
        ->assertCreated();
    $programId = (string) $created->json('data.id');

    $this->putJson("/api/v1/academic/programs/{$programId}/courses", [
        'course_ids' => [$firstCourse['id'], $secondCourse['id']],
    ])->assertOk();
    $this->postJson("/api/v1/academic/programs/{$programId}/publish")->assertOk();

    $this->putJson("/api/v1/academic/programs/{$programId}/courses", [
        'course_ids' => [$secondCourse['id'], $firstCourse['id']],
    ])->assertOk()
        ->assertJsonPath('data.courses.0.course_id', $secondCourse['id'])
        ->assertJsonPath('data.courses.1.course_id', $firstCourse['id']);

    $this->putJson("/api/v1/academic/programs/{$programId}/courses", [
        'course_ids' => [],
    ])->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonValidationErrors(['course_ids']);

    $this->putJson("/api/v1/academic/programs/{$programId}/courses", [
        'course_ids' => [$draftCourseId],
    ])->assertUnprocessable()
        ->assertJsonPath('code', 'PROGRAM_COURSE_NOT_PUBLISHED');

    $this->getJson('/api/v1/academic/programs')
        ->assertOk()
        ->assertJsonPath('data.0.courses.0.course_id', $secondCourse['id'])
        ->assertJsonPath('data.0.courses.1.course_id', $firstCourse['id']);
});
