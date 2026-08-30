<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

it('limita los intentos de inicio de sesion por api a 5 por minuto', function (): void {
    /** @var TestCase $this */
    $email = sprintf('%s@edudrive.cr', Str::uuid());

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->postJson('/api/v1/auth/login', ['email' => $email, 'password' => 'clave-incorrecta']);
    }

    $this->postJson('/api/v1/auth/login', ['email' => $email, 'password' => 'clave-incorrecta'])
        ->assertStatus(429)
        ->assertJsonPath('code', 'TOO_MANY_REQUESTS');
});

it('limita los intentos de inicio de sesion web a 5 por minuto', function (): void {
    /** @var TestCase $this */
    $email = sprintf('%s@edudrive.cr', Str::uuid());

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->post('/login', ['email' => $email, 'password' => 'clave-incorrecta']);
    }

    $this->post('/login', ['email' => $email, 'password' => 'clave-incorrecta'])
        ->assertStatus(429);
});

it('limita los registros a 5 por minuto', function (): void {
    /** @var TestCase $this */
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Usuario de prueba',
            'email' => sprintf('%s@edudrive.cr', Str::uuid()),
            'password' => 'clave-valida-123',
        ]);
    }

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Usuario de prueba',
        'email' => sprintf('%s@edudrive.cr', Str::uuid()),
        'password' => 'clave-valida-123',
    ])
        ->assertStatus(429)
        ->assertJsonPath('code', 'TOO_MANY_REQUESTS');
});

it('limita la verificacion de correo a 10 por minuto', function (): void {
    /** @var TestCase $this */
    for ($attempt = 1; $attempt <= 10; $attempt++) {
        $this->postJson('/api/v1/auth/verify-email', [
            'email' => sprintf('%s@edudrive.cr', Str::uuid()),
            'token' => 'token-invalido',
        ]);
    }

    $this->postJson('/api/v1/auth/verify-email', [
        'email' => sprintf('%s@edudrive.cr', Str::uuid()),
        'token' => 'token-invalido',
    ])->assertStatus(429);
});

it('limita el reenvio de verificacion a 5 por minuto', function (): void {
    /** @var TestCase $this */
    $email = sprintf('%s@edudrive.cr', Str::uuid());

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->postJson('/api/v1/auth/resend-verification', ['email' => $email]);
    }

    $this->postJson('/api/v1/auth/resend-verification', ['email' => $email])
        ->assertStatus(429);
});

it('no acumula el limite de inicio de sesion entre correos distintos', function (): void {
    /** @var TestCase $this */
    $hasher = app(PasswordHasher::class);
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario valido',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $user->activate(new DateTimeImmutable);
    app(UserRepository::class)->save($user);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => sprintf('%s@edudrive.cr', Str::uuid()),
            'password' => 'clave-incorrecta',
        ]);
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->assertOk();
});
