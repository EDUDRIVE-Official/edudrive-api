<?php

declare(strict_types=1);

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

it('muestra el formulario de login a un invitado', function (): void {
    /** @var TestCase $this */
    $this->get('/login')->assertOk()->assertSeeText('Ingresar');
});

it('inicia sesión con credenciales válidas y redirige a mi perfil cuando no tiene permisos administrativos', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);
    $hasher = app(PasswordHasher::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario Web',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $user->activate(new DateTimeImmutable);
    $repository->save($user);

    $response = $this->post('/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ]);

    $response->assertRedirect('/mi-perfil');
    $this->assertAuthenticatedAs(UserModel::query()->findOrFail($user->id()));
});

it('inicia sesión con credenciales válidas y redirige al panel de organizaciones cuando tiene permisos administrativos', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);
    $hasher = app(PasswordHasher::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Administradora Web',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $user->activate(new DateTimeImmutable);
    $repository->save($user);

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::SuperAdmin,
            organizationId: null,
        ),
    );

    $response = $this->post('/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ]);

    $response->assertRedirect('/organizations');
});

it('registra la fecha de ultimo inicio de sesion al iniciar sesion', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);
    $hasher = app(PasswordHasher::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario Web',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $user->activate(new DateTimeImmutable);
    $repository->save($user);

    expect($repository->findById($user->id())?->lastLoginAt())->toBeNull();

    $this->post('/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ]);

    expect($repository->findById($user->id())?->lastLoginAt())->not->toBeNull();
});

it('rechaza credenciales inválidas y vuelve al formulario con un error', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);
    $hasher = app(PasswordHasher::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario Web',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $user->activate(new DateTimeImmutable);
    $repository->save($user);

    $response = $this->post('/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-incorrecta',
    ]);

    $response->assertRedirect();
    $this->assertGuest();
    $response->assertSessionHas('loginError');
});

it('rechaza el login de un usuario que todavía no puede autenticarse', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);
    $hasher = app(PasswordHasher::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario Pendiente',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $repository->save($user);

    $response = $this->post('/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ]);

    $response->assertRedirect();
    $this->assertGuest();
});

it('cierra la sesión y redirige al login', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);
    $hasher = app(PasswordHasher::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario Web',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $user->activate(new DateTimeImmutable);
    $repository->save($user);

    $model = UserModel::query()->findOrFail($user->id());
    $this->actingAs($model);

    $response = $this->post('/logout');

    $response->assertRedirect('/login');
    $this->assertGuest();
});
