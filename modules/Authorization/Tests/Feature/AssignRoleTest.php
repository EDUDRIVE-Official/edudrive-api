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

function registerUserForAssignRoleTest(): UserModel
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

    return UserModel::query()->findOrFail($user->id());
}

it('permite asignar un rol cuando quien llama es superadministrador', function (): void {
    $superAdmin = registerUserForAssignRoleTest();

    /** @var TestCase $this */
    $this->app->make(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $superAdmin->id,
            role: Role::SuperAdmin,
            organizationId: null,
        ),
    );

    Sanctum::actingAs($superAdmin);

    $targetUser = registerUserForAssignRoleTest();

    postJson('/api/v1/authorization/role-assignments', [
        'user_id' => $targetUser->id,
        'role' => 'teacher',
    ])
        ->assertCreated()
        ->assertJsonPath('data.userId', $targetUser->id)
        ->assertJsonPath('data.role', 'teacher');

    assertDatabaseHas('authorization_role_assignments', [
        'user_id' => $targetUser->id,
        'role' => 'teacher',
    ]);
});

it('rechaza la asignación de roles a quien no es superadministrador', function (): void {
    $student = registerUserForAssignRoleTest();

    /** @var TestCase $this */
    $this->app->make(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $student->id,
            role: Role::Student,
            organizationId: null,
        ),
    );

    Sanctum::actingAs($student);

    postJson('/api/v1/authorization/role-assignments', [
        'user_id' => (string) Str::uuid(),
        'role' => 'teacher',
    ])->assertForbidden();
});

it('rechaza la asignación de roles sin autenticación', function (): void {
    postJson('/api/v1/authorization/role-assignments', [
        'user_id' => (string) Str::uuid(),
        'role' => 'teacher',
    ])->assertUnauthorized();
});

it('permite asignar el primer rol mediante el comando de consola', function (): void {
    /** @var TestCase $this */
    $user = registerUserForAssignRoleTest();

    $this->artisan(
        'authorization:assign-role',
        ['userId' => $user->id, 'role' => 'super_admin'],
    )->assertSuccessful();

    assertDatabaseHas('authorization_role_assignments', [
        'user_id' => $user->id,
        'role' => 'super_admin',
    ]);
});
