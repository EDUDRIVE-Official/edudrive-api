<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

use function Pest\Laravel\assertDatabaseHas;

use Tests\TestCase;

function persistedRoleAssignmentWebTestUser(): User
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user;
}

it('redirige a un invitado que intenta ver el formulario de asignacion de roles', function (): void {
    /** @var TestCase $this */
    $this->get('/roles/assign')->assertRedirect(route('login'));
});

it('rechaza a un usuario autenticado sin el permiso roles.manage', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Docente Web',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    $repository->save($user);

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::Teacher,
            organizationId: null,
        ),
    );

    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/roles/assign')->assertForbidden();
    $this->post('/roles/assign', [])->assertForbidden();
});

it('muestra el formulario de asignacion de roles a un superadministrador', function (): void {
    /** @var TestCase $this */
    $admin = actingAsSuperAdminUser();
    $this->actingAs($admin, 'web');

    $this->get('/roles/assign')
        ->assertOk()
        ->assertSeeText('Asignar rol');
});

it('asigna un rol a un usuario y redirige al formulario con un mensaje de exito', function (): void {
    /** @var TestCase $this */
    $target = persistedRoleAssignmentWebTestUser();

    $admin = actingAsSuperAdminUser();
    $this->actingAs($admin, 'web');

    $response = $this->post('/roles/assign', [
        'user_id' => $target->id(),
        'role' => 'teacher',
    ]);

    $response->assertRedirect(route('roles.assign'));
    $response->assertSessionHas('status');

    assertDatabaseHas('authorization_role_assignments', [
        'user_id' => $target->id(),
        'role' => 'teacher',
        'organization_id' => null,
    ]);
});

it('vuelve al formulario con errores cuando faltan datos obligatorios', function (): void {
    /** @var TestCase $this */
    $admin = actingAsSuperAdminUser();
    $this->actingAs($admin, 'web');

    $response = $this->post('/roles/assign', []);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['user_id', 'role']);
});

it('vuelve al formulario con error cuando el rol es invalido', function (): void {
    /** @var TestCase $this */
    $target = persistedRoleAssignmentWebTestUser();

    $admin = actingAsSuperAdminUser();
    $this->actingAs($admin, 'web');

    $response = $this->post('/roles/assign', [
        'user_id' => $target->id(),
        'role' => 'no-existe',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['role']);
});
