<?php

declare(strict_types=1);

use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Enums\UserStatus;
use Modules\Identity\Domain\Exceptions\InvalidUserName;
use Modules\Identity\Domain\ValueObjects\Email;

it('registra un usuario con estado pendiente', function (): void {
    $registeredAt = new DateTimeImmutable('2026-07-24 12:00:00');

    $user = User::register(
        id: '01900000-0000-7000-8000-000000000001',
        name: ' Abel Campos ',
        email: Email::fromString('ABEL@EDUDRIVE.CR'),
        passwordHash: 'hashed-password',
        registeredAt: $registeredAt,
    );

    expect($user->id())
        ->toBe('01900000-0000-7000-8000-000000000001')
        ->and($user->name())
        ->toBe('Abel Campos')
        ->and($user->email()->value())
        ->toBe('abel@edudrive.cr')
        ->and($user->passwordHash())
        ->toBe('hashed-password')
        ->and($user->status())
        ->toBe(UserStatus::Pending)
        ->and($user->emailVerifiedAt())
        ->toBeNull()
        ->and($user->createdAt())
        ->toEqual($registeredAt)
        ->and($user->updatedAt())
        ->toEqual($registeredAt);
});

it('activa y verifica el correo del usuario', function (): void {
    $registeredAt = new DateTimeImmutable('2026-07-24 12:00:00');
    $activatedAt = new DateTimeImmutable('2026-07-24 12:05:00');

    $user = User::register(
        id: '01900000-0000-7000-8000-000000000001',
        name: 'Abel Campos',
        email: Email::fromString('abel@edudrive.cr'),
        passwordHash: 'hashed-password',
        registeredAt: $registeredAt,
    );

    $user->activate($activatedAt);

    expect($user->status())
        ->toBe(UserStatus::Active)
        ->and($user->emailVerifiedAt())
        ->toEqual($activatedAt)
        ->and($user->updatedAt())
        ->toEqual($activatedAt);
});

it('devuelve el usuario a pendiente cuando cambia su correo', function (): void {
    $registeredAt = new DateTimeImmutable('2026-07-24 12:00:00');
    $activatedAt = new DateTimeImmutable('2026-07-24 12:05:00');
    $changedAt = new DateTimeImmutable('2026-07-24 12:10:00');

    $user = User::register(
        id: '01900000-0000-7000-8000-000000000001',
        name: 'Abel Campos',
        email: Email::fromString('abel@edudrive.cr'),
        passwordHash: 'hashed-password',
        registeredAt: $registeredAt,
    );

    $user->activate($activatedAt);
    $user->changeEmail(
        Email::fromString('nuevo@edudrive.cr'),
        $changedAt,
    );

    expect($user->email()->value())
        ->toBe('nuevo@edudrive.cr')
        ->and($user->status())
        ->toBe(UserStatus::Pending)
        ->and($user->emailVerifiedAt())
        ->toBeNull()
        ->and($user->updatedAt())
        ->toEqual($changedAt);
});

it('no se considera menor sin fecha de nacimiento registrada', function (): void {
    $user = User::register(
        id: '01900000-0000-7000-8000-000000000001',
        name: 'Abel Campos',
        email: Email::fromString('abel@edudrive.cr'),
        passwordHash: 'hashed-password',
    );

    expect($user->dateOfBirth())->toBeNull()
        ->and($user->isMinor())->toBeFalse();
});

it('se considera menor cuando aun no cumple 18 anos', function (): void {
    $user = User::register(
        id: '01900000-0000-7000-8000-000000000001',
        name: 'Abel Campos',
        email: Email::fromString('abel@edudrive.cr'),
        passwordHash: 'hashed-password',
        dateOfBirth: new DateTimeImmutable('2015-08-28'),
    );

    expect($user->isMinor(new DateTimeImmutable('2026-08-28')))->toBeTrue();
});

it('deja de considerarse menor exactamente al cumplir 18 anos', function (): void {
    $user = User::register(
        id: '01900000-0000-7000-8000-000000000001',
        name: 'Abel Campos',
        email: Email::fromString('abel@edudrive.cr'),
        passwordHash: 'hashed-password',
        dateOfBirth: new DateTimeImmutable('2008-08-28'),
    );

    expect($user->isMinor(new DateTimeImmutable('2026-08-28')))->toBeFalse()
        ->and($user->isMinor(new DateTimeImmutable('2026-08-27')))->toBeTrue();
});

it('rechaza un nombre vacío', function (): void {
    User::register(
        id: '01900000-0000-7000-8000-000000000001',
        name: '   ',
        email: Email::fromString('abel@edudrive.cr'),
        passwordHash: 'hashed-password',
    );
})->throws(InvalidUserName::class);
