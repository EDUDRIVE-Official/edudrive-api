<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

use Tests\TestCase;

it('crea un curso académico', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $response = postJson(
        '/api/v1/academic/courses',
        [
            'code' => 'edu-010',
            'title' => 'Introducción a la seguridad vial',
            'description' => 'Curso base de EDUDRIVE.',
            'objectives' => 'Comprender los principios básicos.',
            'prerequisites' => 'Ninguno.',
            'modality' => 'virtual',
            'duration_hours' => 20,
        ],
    );

    $response
        ->assertCreated()
        ->assertJsonPath('data.code', 'EDU-010')
        ->assertJsonPath(
            'data.title',
            'Introducción a la seguridad vial',
        )
        ->assertJsonPath(
            'data.description',
            'Curso base de EDUDRIVE.',
        )
        ->assertJsonPath('data.objectives', 'Comprender los principios básicos.')
        ->assertJsonPath('data.prerequisites', 'Ninguno.')
        ->assertJsonPath('data.modality', 'virtual')
        ->assertJsonPath('data.duration_hours', 20)
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonStructure([
            'data' => [
                'id',
                'code',
                'title',
                'description',
                'objectives',
                'prerequisites',
                'modality',
                'duration_hours',
                'status',
            ],
        ]);

    assertDatabaseHas('academic_courses', [
        'code' => 'EDU-010',
        'title' => 'Introducción a la seguridad vial',
        'description' => 'Curso base de EDUDRIVE.',
        'objectives' => 'Comprender los principios básicos.',
        'prerequisites' => 'Ninguno.',
        'modality' => 'virtual',
        'duration_hours' => 20,
        'status' => 'draft',
    ]);
});

it('crea un curso académico sin los campos opcionales nuevos', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $response = postJson(
        '/api/v1/academic/courses',
        [
            'code' => 'edu-011',
            'title' => 'Conducción responsable',
        ],
    );

    $response
        ->assertCreated()
        ->assertJsonPath('data.objectives', null)
        ->assertJsonPath('data.prerequisites', null)
        ->assertJsonPath('data.modality', null)
        ->assertJsonPath('data.duration_hours', null);
});

it('rechaza la creación de un curso sin datos obligatorios', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    postJson('/api/v1/academic/courses', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'code',
            'title',
        ]);
});

it('rechaza un código con formato inválido', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    postJson(
        '/api/v1/academic/courses',
        [
            'code' => 'EDU_010',
            'title' => 'Curso inválido',
        ],
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'code',
        ]);
});

it('rechaza una modalidad inválida', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    postJson(
        '/api/v1/academic/courses',
        [
            'code' => 'EDU-012',
            'title' => 'Curso con modalidad inválida',
            'modality' => 'no-existe',
        ],
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'modality',
        ]);
});

it('rechaza un código de curso duplicado', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    postJson('/api/v1/academic/courses', [
        'code' => 'EDU-013',
        'title' => 'Curso original',
    ])->assertCreated();

    postJson('/api/v1/academic/courses', [
        'code' => 'edu-013',
        'title' => 'Curso duplicado',
    ])
        ->assertConflict()
        ->assertJsonPath('code', 'COURSE_CODE_ALREADY_EXISTS');
});

it('rechaza la creación sin autenticación', function (): void {
    postJson('/api/v1/academic/courses', [
        'code' => 'EDU-014',
        'title' => 'Curso sin sesión',
    ])->assertUnauthorized();
});

it('rechaza la creación de cursos a un usuario sin el permiso courses.manage', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::Student,
            organizationId: null,
        ),
    );

    Sanctum::actingAs(UserModel::query()->findOrFail($user->id()));

    postJson('/api/v1/academic/courses', [
        'code' => 'EDU-015',
        'title' => 'Curso sin permiso',
    ])->assertForbidden();
});
