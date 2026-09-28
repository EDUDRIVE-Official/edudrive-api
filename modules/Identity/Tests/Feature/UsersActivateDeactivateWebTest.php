<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

it('activa un usuario y redirige a la lista con un mensaje de exito', function (): void {
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

    $response = $this->post("/users/{$pending->id()}/activate");

    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('status');

    expect($repository->findById($pending->id())?->status()->value)->toBe('active');
});

it('desactiva un usuario y redirige a la lista con un mensaje de exito', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

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

    $response = $this->post("/users/{$active->id()}/deactivate");

    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('status');

    expect($repository->findById($active->id())?->status()->value)->toBe('inactive');
});

it('rechaza activar y desactivar sin el permiso users.manage', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $target = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario Objetivo',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    $repository->save($target);

    $teacher = User::register(
        id: (string) Str::uuid(),
        name: 'Docente Web',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    $repository->save($teacher);

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $teacher->id(),
            role: Role::Teacher,
            organizationId: null,
        ),
    );

    $this->actingAs(UserModel::query()->findOrFail($teacher->id()), 'web');

    $this->post("/users/{$target->id()}/activate")->assertForbidden();
    $this->post("/users/{$target->id()}/deactivate")->assertForbidden();
});

it('permite vincular y retirar acompañamiento familiar desde usuarios', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);
    $guardian = User::register((string) Str::uuid(), 'Persona Adulta', Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())), 'hash', dateOfBirth: new DateTimeImmutable('1985-01-01'));
    $minor = User::register((string) Str::uuid(), 'Persona Menor', Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())), 'hash', dateOfBirth: new DateTimeImmutable('2015-01-01'));
    $repository->save($guardian);
    $repository->save($minor);
    $this->actingAs(actingAsSuperAdminUser(), 'web');

    $this->post(route('users.guardians.store'), [
        'guardian_user_id' => $guardian->id(),
        'minor_user_id' => $minor->id(),
    ])->assertRedirect(route('users.index'))->assertSessionHas('status');

    $relationship = app(GuardianRelationshipRepository::class)->findActiveByGuardianAndMinor($guardian->id(), $minor->id());
    expect($relationship)->not->toBeNull();
    assert($relationship !== null);

    $this->delete(route('users.guardians.destroy', $relationship->id()))
        ->assertRedirect(route('users.index'))->assertSessionHas('status');
    expect(app(GuardianRelationshipRepository::class)->findActiveByGuardianAndMinor($guardian->id(), $minor->id()))->toBeNull();
});
