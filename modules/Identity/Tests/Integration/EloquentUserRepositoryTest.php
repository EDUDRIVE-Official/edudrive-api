<?php

declare(strict_types=1);

use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Enums\UserStatus;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
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
