<?php

declare(strict_types=1);

use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Enums\UserStatus;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

it('guarda y recupera un usuario por identificador', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(UserRepository::class);

    $registeredAt = new DateTimeImmutable('2026-07-24 12:00:00');

    $user = User::register(
        id: '01900000-0000-7000-8000-000000000001',
        name: 'Abel Campos',
        email: Email::fromString('abel@edudrive.cr'),
        passwordHash: 'hashed-password',
        registeredAt: $registeredAt,
    );

    $repository->save($user);

    $persistedUser = $repository->findById($user->id());

    expect($persistedUser)
        ->not->toBeNull()
        ->and($persistedUser?->id())
        ->toBe($user->id())
        ->and($persistedUser?->name())
        ->toBe('Abel Campos')
        ->and($persistedUser?->email()->value())
        ->toBe('abel@edudrive.cr')
        ->and($persistedUser?->status())
        ->toBe(UserStatus::Pending);
});

it('busca un usuario por correo normalizado', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(UserRepository::class);

    $user = User::register(
        id: '01900000-0000-7000-8000-000000000002',
        name: 'Usuario EDUDRIVE',
        email: Email::fromString('usuario@edudrive.cr'),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    $persistedUser = $repository->findByEmail(
        Email::fromString('USUARIO@EDUDRIVE.CR'),
    );

    expect($persistedUser)
        ->not->toBeNull()
        ->and($persistedUser?->id())
        ->toBe($user->id())
        ->and(
            $repository->existsByEmail(
                Email::fromString('usuario@edudrive.cr'),
            ),
        )
        ->toBeTrue();
});

it('actualiza el estado de un usuario existente', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(UserRepository::class);

    $user = User::register(
        id: '01900000-0000-7000-8000-000000000003',
        name: 'Usuario EDUDRIVE',
        email: Email::fromString('estado@edudrive.cr'),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    $user->activate(new DateTimeImmutable('2026-07-24 13:00:00'));

    $repository->save($user);

    $persistedUser = $repository->findById($user->id());

    expect($persistedUser)
        ->not->toBeNull()
        ->and($persistedUser?->status())
        ->toBe(UserStatus::Active)
        ->and($persistedUser?->emailVerifiedAt())
        ->not->toBeNull();
});

it('guarda y recupera la fecha de ultimo inicio de sesion', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(UserRepository::class);

    $user = User::register(
        id: '01900000-0000-7000-8000-000000000006',
        name: 'Usuario EDUDRIVE',
        email: Email::fromString('login@edudrive.cr'),
        passwordHash: 'hashed-password',
    );
    $repository->save($user);

    expect($repository->findById($user->id())?->lastLoginAt())->toBeNull();

    $loginAt = new DateTimeImmutable('2026-08-27T10:00:00+00:00');
    $user->recordLogin($loginAt);
    $repository->save($user);

    expect($repository->findById($user->id())?->lastLoginAt())
        ->toEqual($loginAt);
});

it('elimina un usuario existente', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(UserRepository::class);

    $user = User::register(
        id: '01900000-0000-7000-8000-000000000007',
        name: 'Usuario a eliminar',
        email: Email::fromString('eliminar@edudrive.cr'),
        passwordHash: 'hashed-password',
    );
    $repository->save($user);

    $repository->delete($user->id());

    expect($repository->findById($user->id()))->toBeNull();
});

it('encuentra usuarios inactivos antes de un umbral por ultimo inicio de sesion o fecha de registro', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(UserRepository::class);

    $inactiveByLogin = User::register(
        id: '01900000-0000-7000-8000-000000000008',
        name: 'Inactivo por login',
        email: Email::fromString('inactivo-login@edudrive.cr'),
        passwordHash: 'hashed-password',
        registeredAt: new DateTimeImmutable('2020-01-01T00:00:00+00:00'),
    );
    $inactiveByLogin->recordLogin(new DateTimeImmutable('2020-06-01T00:00:00+00:00'));
    $repository->save($inactiveByLogin);

    $inactiveNeverLoggedIn = User::register(
        id: '01900000-0000-7000-8000-000000000009',
        name: 'Inactivo sin login',
        email: Email::fromString('inactivo-sin-login@edudrive.cr'),
        passwordHash: 'hashed-password',
        registeredAt: new DateTimeImmutable('2020-01-01T00:00:00+00:00'),
    );
    $repository->save($inactiveNeverLoggedIn);
    // Eloquent gestiona created_at automaticamente al guardar (ignora el valor del mapper),
    // por lo que se fuerza directamente para simular un registro antiguo real.
    UserModel::query()->whereKey($inactiveNeverLoggedIn->id())->update(['created_at' => '2020-01-01 00:00:00']);

    $active = User::register(
        id: '01900000-0000-7000-8000-000000000010',
        name: 'Activo',
        email: Email::fromString('activo@edudrive.cr'),
        passwordHash: 'hashed-password',
        registeredAt: new DateTimeImmutable('2020-01-01T00:00:00+00:00'),
    );
    $active->recordLogin(new DateTimeImmutable('now'));
    $repository->save($active);

    $inactive = $repository->findInactiveBefore(new DateTimeImmutable('-3 years'));

    $inactiveIds = array_map(static fn (User $user): string => $user->id(), $inactive);

    expect($inactiveIds)->toContain($inactiveByLogin->id())
        ->and($inactiveIds)->toContain($inactiveNeverLoggedIn->id())
        ->and($inactiveIds)->not->toContain($active->id());
});

it('lista todos los usuarios registrados', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(UserRepository::class);

    $repository->save(User::register(
        id: '01900000-0000-7000-8000-000000000004',
        name: 'Usuario Uno',
        email: Email::fromString('uno@edudrive.cr'),
        passwordHash: 'hashed-password',
    ));
    $repository->save(User::register(
        id: '01900000-0000-7000-8000-000000000005',
        name: 'Usuario Dos',
        email: Email::fromString('dos@edudrive.cr'),
        passwordHash: 'hashed-password',
    ));

    expect($repository->all())->toHaveCount(2);
});
