<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

function registerRevokeSessionTestUser(): User
{
    $hasher = app(PasswordHasher::class);
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de sesiones',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $user->activate(new DateTimeImmutable);
    app(UserRepository::class)->save($user);

    return $user;
}

it('revoca una sesion especifica sin afectar las demas', function (): void {
    /** @var TestCase $this */
    $user = registerRevokeSessionTestUser();

    $firstToken = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
        'token_name' => 'primer-dispositivo',
    ])->json('data.token.access_token');

    $secondToken = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
        'token_name' => 'segundo-dispositivo',
    ])->json('data.token.access_token');

    $sessions = $this->withHeader('Authorization', 'Bearer '.$secondToken)
        ->getJson('/api/v1/auth/sessions')
        ->json('data.sessions');

    $firstSessionId = collect($sessions)->firstWhere('name', 'primer-dispositivo')['id'];

    $this->withHeader('Authorization', 'Bearer '.$secondToken)
        ->deleteJson('/api/v1/auth/sessions/'.$firstSessionId)
        ->assertOk();

    Auth::forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$firstToken)
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();

    $this->withHeader('Authorization', 'Bearer '.$secondToken)
        ->getJson('/api/v1/auth/me')
        ->assertOk();
});

it('rechaza revocar la sesion de otro usuario', function (): void {
    /** @var TestCase $this */
    $owner = registerRevokeSessionTestUser();
    $otherUser = registerRevokeSessionTestUser();

    $ownerToken = $this->postJson('/api/v1/auth/login', [
        'email' => $owner->email()->value(),
        'password' => 'clave-valida-123',
    ])->json('data.token.access_token');

    $ownerSessionId = $this->withHeader('Authorization', 'Bearer '.$ownerToken)
        ->getJson('/api/v1/auth/sessions')
        ->json('data.sessions.0.id');

    Auth::forgetGuards();

    $otherToken = $this->postJson('/api/v1/auth/login', [
        'email' => $otherUser->email()->value(),
        'password' => 'clave-valida-123',
    ])->json('data.token.access_token');

    $this->withHeader('Authorization', 'Bearer '.$otherToken)
        ->deleteJson('/api/v1/auth/sessions/'.$ownerSessionId)
        ->assertStatus(404)
        ->assertJsonPath('code', 'SESSION_NOT_FOUND');
});

it('rechaza revocar una sesion inexistente', function (): void {
    /** @var TestCase $this */
    $user = registerRevokeSessionTestUser();

    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->json('data.token.access_token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/v1/auth/sessions/999999')
        ->assertStatus(404)
        ->assertJsonPath('code', 'SESSION_NOT_FOUND');
});

it('requiere autenticacion para revocar una sesion', function (): void {
    /** @var TestCase $this */
    $this->deleteJson('/api/v1/auth/sessions/1')->assertUnauthorized();
});
