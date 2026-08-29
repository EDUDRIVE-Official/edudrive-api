<?php

declare(strict_types=1);

use Modules\Identity\Domain\Entities\PasswordResetToken;
use Modules\Identity\Domain\ValueObjects\Email;

it('emite un token con la fecha de creacion dada', function (): void {
    $createdAt = new DateTimeImmutable('2026-08-29 10:00:00');

    $token = PasswordResetToken::issue(
        email: Email::fromString('abel@edudrive.cr'),
        tokenHash: 'hash-del-token',
        createdAt: $createdAt,
    );

    expect($token->email()->value())->toBe('abel@edudrive.cr')
        ->and($token->tokenHash())->toBe('hash-del-token')
        ->and($token->createdAt())->toEqual($createdAt);
});

it('reconstruye un token desde persistencia', function (): void {
    $createdAt = new DateTimeImmutable('2026-08-29 10:00:00');

    $token = PasswordResetToken::reconstitute(
        email: Email::fromString('abel@edudrive.cr'),
        tokenHash: 'hash-del-token',
        createdAt: $createdAt,
    );

    expect($token->email()->value())->toBe('abel@edudrive.cr')
        ->and($token->tokenHash())->toBe('hash-del-token')
        ->and($token->createdAt())->toEqual($createdAt);
});

it('confirma si un hash coincide con el del token', function (): void {
    $token = PasswordResetToken::issue(
        email: Email::fromString('abel@edudrive.cr'),
        tokenHash: 'hash-correcto',
        createdAt: new DateTimeImmutable,
    );

    expect($token->matchesHash('hash-correcto'))->toBeTrue()
        ->and($token->matchesHash('hash-incorrecto'))->toBeFalse();
});

it('no se considera expirado dentro de la ventana de sesenta minutos', function (): void {
    $createdAt = new DateTimeImmutable('2026-08-29 10:00:00');

    $token = PasswordResetToken::issue(
        email: Email::fromString('abel@edudrive.cr'),
        tokenHash: 'hash-del-token',
        createdAt: $createdAt,
    );

    expect($token->isExpired(new DateTimeImmutable('2026-08-29 10:59:59')))->toBeFalse();
});

it('se considera expirado despues de sesenta minutos', function (): void {
    $createdAt = new DateTimeImmutable('2026-08-29 10:00:00');

    $token = PasswordResetToken::issue(
        email: Email::fromString('abel@edudrive.cr'),
        tokenHash: 'hash-del-token',
        createdAt: $createdAt,
    );

    expect($token->isExpired(new DateTimeImmutable('2026-08-29 11:00:01')))->toBeTrue();
});
