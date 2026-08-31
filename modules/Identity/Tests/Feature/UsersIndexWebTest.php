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
use Tests\TestCase;

it('redirige a un invitado que intenta ver la lista de usuarios', function (): void {
    /** @var TestCase $this */
    $this->get('/users')->assertRedirect(route('login'));
});

it('rechaza a un usuario autenticado sin permiso users.view', function (): void {
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

    $this->get('/users')->assertForbidden();
});

it('muestra la lista de usuarios a un superadministrador', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $pending = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario Pendiente',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    $repository->save($pending);

    $admin = actingAsSuperAdminUser();
    $this->actingAs($admin, 'web');

    $response = $this->get('/users');

    $response->assertOk();
    $response->assertSeeText('Usuario Pendiente');
    $response->assertSeeText('Pendiente');
});

it('muestra el boton activar para un usuario que no esta activo y desactivar para uno que si lo esta', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $pending = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario Pendiente',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    $repository->save($pending);

    $active = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario Activo',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    $active->activate(new DateTimeImmutable);
    $repository->save($active);

    $admin = actingAsSuperAdminUser();
    $this->actingAs($admin, 'web');

    $response = $this->get('/users');

    $response->assertOk();
    $response->assertSee('action="'.route('users.activate', $pending->id()).'"', false);
    $response->assertDontSee('action="'.route('users.deactivate', $pending->id()).'"', false);
    $response->assertSee('action="'.route('users.deactivate', $active->id()).'"', false);
    $response->assertDontSee('action="'.route('users.activate', $active->id()).'"', false);
});
