<?php

declare(strict_types=1);

use Modules\Identity\Domain\Entities\EmailVerificationToken;
use Modules\Identity\Domain\Repositories\EmailVerificationTokenRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

it('guarda y recupera un token de verificacion por correo', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(EmailVerificationTokenRepository::class);

    $createdAt = new DateTimeImmutable('2026-08-29 10:00:00');
    $email = Email::fromString('abel@edudrive.cr');

    $repository->save(EmailVerificationToken::issue(
        email: $email,
        tokenHash: 'hash-del-token',
        createdAt: $createdAt,
    ));

    $persisted = $repository->findByEmail($email);

    expect($persisted)->not->toBeNull()
        ->and($persisted?->email()->value())->toBe('abel@edudrive.cr')
        ->and($persisted?->tokenHash())->toBe('hash-del-token')
        ->and($persisted?->createdAt())->toEqual($createdAt);
});

it('reemplaza el token existente al guardar uno nuevo para el mismo correo', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(EmailVerificationTokenRepository::class);
    $email = Email::fromString('abel@edudrive.cr');

    $repository->save(EmailVerificationToken::issue(email: $email, tokenHash: 'hash-viejo'));
    $repository->save(EmailVerificationToken::issue(email: $email, tokenHash: 'hash-nuevo'));

    expect($repository->findByEmail($email)?->tokenHash())->toBe('hash-nuevo');
});

it('devuelve null cuando no hay ningun token para el correo', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(EmailVerificationTokenRepository::class);

    expect($repository->findByEmail(Email::fromString('no-existe@edudrive.cr')))->toBeNull();
});

it('elimina el token de un correo', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(EmailVerificationTokenRepository::class);
    $email = Email::fromString('abel@edudrive.cr');

    $repository->save(EmailVerificationToken::issue(email: $email, tokenHash: 'hash-del-token'));
    $repository->deleteByEmail($email);

    expect($repository->findByEmail($email))->toBeNull();
});
