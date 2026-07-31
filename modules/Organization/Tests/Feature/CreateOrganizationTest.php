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

/**
 * Local helper (distinct from the shared `actingAsAuthenticatedUser()` in
 * tests/Pest.php, which returns a user with no role) because write endpoints
 * on this module now require the `organizations.manage` permission, which
 * only `SuperAdmin` holds per `RolePermissions`.
 */
function actingAsSuperAdminUserForCreateOrganizationTest(): UserModel
{
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    $model = UserModel::query()->findOrFail($user->id());

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::SuperAdmin,
            organizationId: null,
        ),
    );

    Sanctum::actingAs($model);

    return $model;
}

it('crea una organización cuando el usuario está autenticado', function (): void {
    actingAsSuperAdminUserForCreateOrganizationTest();

    $response = postJson('/api/v1/organizations', [
        'name' => 'Escuela de Manejo EDUDRIVE',
        'type' => 'driving_school',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Escuela de Manejo EDUDRIVE')
        ->assertJsonPath('data.type', 'driving_school')
        ->assertJsonStructure([
            'data' => ['id', 'name', 'type'],
        ]);

    assertDatabaseHas('organizations', [
        'name' => 'Escuela de Manejo EDUDRIVE',
        'type' => 'driving_school',
    ]);
});

it('rechaza la creación sin autenticación', function (): void {
    postJson('/api/v1/organizations', [
        'name' => 'Sin autenticación',
        'type' => 'company',
    ])->assertUnauthorized();
});

it('rechaza datos obligatorios faltantes', function (): void {
    actingAsSuperAdminUserForCreateOrganizationTest();

    postJson('/api/v1/organizations', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'type']);
});

it('rechaza un tipo de organización inválido', function (): void {
    actingAsSuperAdminUserForCreateOrganizationTest();

    postJson('/api/v1/organizations', [
        'name' => 'Organización X',
        'type' => 'not-a-real-type',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('rechaza la creación de organizaciones a un usuario sin el permiso organizations.manage', function (): void {
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

    postJson('/api/v1/organizations', [
        'name' => 'Organización sin permiso',
        'type' => 'company',
    ])->assertForbidden();
});
