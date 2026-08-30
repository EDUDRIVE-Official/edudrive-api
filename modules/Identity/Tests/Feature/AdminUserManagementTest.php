<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Enums\UserStatus;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

function persistedAdminManagedUser(): User
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario administrado',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user;
}

it('lista los usuarios con el permiso users.view', function (): void {
    /** @var TestCase $this */
    $user = persistedAdminManagedUser();
    actingAsRole(Role::SuperAdmin);

    $this->getJson('/api/v1/users')
        ->assertOk()
        ->assertJsonFragment(['id' => $user->id()]);
});

it('rechaza listar usuarios sin el permiso users.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/users')->assertForbidden();
});

it('consulta un usuario por id con el permiso users.view', function (): void {
    /** @var TestCase $this */
    $user = persistedAdminManagedUser();
    actingAsRole(Role::SuperAdmin);

    $this->getJson("/api/v1/users/{$user->id()}")
        ->assertOk()
        ->assertJsonPath('data.id', $user->id())
        ->assertJsonPath('data.status', 'pending');
});

it('activa un usuario con el permiso users.manage', function (): void {
    /** @var TestCase $this */
    $user = persistedAdminManagedUser();
    $admin = actingAsRole(Role::SuperAdmin);

    $this->postJson("/api/v1/users/{$user->id()}/activate")
        ->assertOk()
        ->assertJsonPath('data.status', UserStatus::Active->value);

    $entry = AuditLogModel::query()->where('action', 'identity.account_activated')->latest('occurred_at')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->user_id)->toBe($admin->id)
        ->and($entry->entity_id)->toBe($user->id());
});

it('rechaza activar un usuario sin el permiso users.manage', function (): void {
    /** @var TestCase $this */
    $user = persistedAdminManagedUser();
    actingAsRole(Role::Teacher);

    $this->postJson("/api/v1/users/{$user->id()}/activate")->assertForbidden();
});

it('desactiva un usuario con el permiso users.manage', function (): void {
    /** @var TestCase $this */
    $user = persistedAdminManagedUser();
    $user->activate(new DateTimeImmutable('now'));
    app(UserRepository::class)->save($user);
    $admin = actingAsRole(Role::SuperAdmin);

    $this->postJson("/api/v1/users/{$user->id()}/deactivate")
        ->assertOk()
        ->assertJsonPath('data.status', UserStatus::Inactive->value);

    $entry = AuditLogModel::query()->where('action', 'identity.account_deactivated')->latest('occurred_at')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->user_id)->toBe($admin->id)
        ->and($entry->entity_id)->toBe($user->id());
});

it('rechaza desactivar un usuario sin el permiso users.manage', function (): void {
    /** @var TestCase $this */
    $user = persistedAdminManagedUser();
    actingAsRole(Role::Teacher);

    $this->postJson("/api/v1/users/{$user->id()}/deactivate")->assertForbidden();
});

it('requiere autenticacion para todos los endpoints de administracion de usuarios', function (): void {
    /** @var TestCase $this */
    $user = persistedAdminManagedUser();

    $this->getJson('/api/v1/users')->assertUnauthorized();
    $this->getJson("/api/v1/users/{$user->id()}")->assertUnauthorized();
    $this->postJson("/api/v1/users/{$user->id()}/activate")->assertUnauthorized();
    $this->postJson("/api/v1/users/{$user->id()}/deactivate")->assertUnauthorized();
});
