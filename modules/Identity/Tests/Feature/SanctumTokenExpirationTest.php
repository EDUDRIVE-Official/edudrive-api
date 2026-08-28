<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

it('rechaza un token de acceso emitido antes de la ventana de expiracion configurada', function (): void {
    /** @var TestCase $this */
    $hasher = app(PasswordHasher::class);
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $user->activate(new DateTimeImmutable);
    app(UserRepository::class)->save($user);

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->assertOk();

    $token = $login->json('data.token.access_token');

    PersonalAccessToken::query()->update([
        'created_at' => now()->subMinutes((int) config('sanctum.expiration') + 1),
    ]);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/authorization/me/roles')
        ->assertUnauthorized();
});

it('acepta un token de acceso emitido dentro de la ventana de expiracion configurada', function (): void {
    /** @var TestCase $this */
    $hasher = app(PasswordHasher::class);
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $user->activate(new DateTimeImmutable);
    app(UserRepository::class)->save($user);

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->assertOk();

    $token = $login->json('data.token.access_token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/authorization/me/roles')
        ->assertOk();
});
