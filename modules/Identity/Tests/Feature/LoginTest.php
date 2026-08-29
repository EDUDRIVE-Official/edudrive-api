<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

function registerLoginTestUser(string $password = 'clave-valida-123'): User
{
    $hasher = app(PasswordHasher::class);
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de autenticacion',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash($password),
    );
    $user->activate(new DateTimeImmutable);
    app(UserRepository::class)->save($user);

    return $user;
}

it('inicia sesion correctamente con credenciales validas', function (): void {
    /** @var TestCase $this */
    $user = registerLoginTestUser();

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.id', $user->id())
        ->assertJsonPath('data.user.email', $user->email()->value());

    expect($response->json('data.token.access_token'))->toBeString()->not->toBeEmpty();
});

it('rechaza credenciales invalidas', function (): void {
    /** @var TestCase $this */
    $user = registerLoginTestUser();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-incorrecta',
    ])
        ->assertStatus(401)
        ->assertJsonPath('code', 'INVALID_CREDENTIALS');
});

it('rechaza un usuario inexistente con el mismo codigo que credenciales invalidas', function (): void {
    /** @var TestCase $this */
    $this->postJson('/api/v1/auth/login', [
        'email' => 'no-existe@edudrive.cr',
        'password' => 'cualquier-clave',
    ])
        ->assertStatus(401)
        ->assertJsonPath('code', 'INVALID_CREDENTIALS');
});

it('rechaza el inicio de sesion de un usuario inactivo', function (): void {
    /** @var TestCase $this */
    $user = registerLoginTestUser();
    $user->deactivate(new DateTimeImmutable);
    app(UserRepository::class)->save($user);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])
        ->assertStatus(403)
        ->assertJsonPath('code', 'USER_CANNOT_AUTHENTICATE');
});

it('rechaza el acceso a /me sin token', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('permite el acceso a /me con un token valido', function (): void {
    /** @var TestCase $this */
    $user = registerLoginTestUser();

    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->json('data.token.access_token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id());
});

it('revoca el token actual al cerrar sesion', function (): void {
    /** @var TestCase $this */
    $user = registerLoginTestUser();

    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->json('data.token.access_token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    Auth::forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});

it('revoca todos los tokens del usuario al cerrar todas las sesiones', function (): void {
    /** @var TestCase $this */
    $user = registerLoginTestUser();

    $firstToken = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->json('data.token.access_token');

    $secondToken = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->json('data.token.access_token');

    $this->withHeader('Authorization', 'Bearer '.$firstToken)
        ->postJson('/api/v1/auth/logout-all')
        ->assertOk();

    Auth::forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$firstToken)
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();

    $this->withHeader('Authorization', 'Bearer '.$secondToken)
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});
