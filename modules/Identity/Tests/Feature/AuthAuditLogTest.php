<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

function registerActiveAuditUser(): User
{
    $hasher = app(PasswordHasher::class);
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de auditoria',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $user->activate(new DateTimeImmutable);
    app(UserRepository::class)->save($user);

    return $user;
}

it('audita un inicio de sesion fallido por credenciales invalidas', function (): void {
    /** @var TestCase $this */
    $user = registerActiveAuditUser();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-incorrecta',
    ])->assertStatus(500);

    $entry = AuditLogModel::query()->where('action', 'auth.login')->latest('occurred_at')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->outcome)->toBe('failure')
        ->and($entry->user_id)->toBe($user->id());
});

it('audita un inicio de sesion exitoso con la ip de la peticion', function (): void {
    /** @var TestCase $this */
    $user = registerActiveAuditUser();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->assertOk();

    $entry = AuditLogModel::query()->where('action', 'auth.login')->latest('occurred_at')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->outcome)->toBe('success')
        ->and($entry->user_id)->toBe($user->id())
        ->and($entry->ip)->not->toBeNull();
});

it('audita el cierre de sesion con el id del usuario autenticado', function (): void {
    /** @var TestCase $this */
    $user = registerActiveAuditUser();

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->assertOk();

    $token = $login->json('data.token.access_token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    $entry = AuditLogModel::query()->where('action', 'auth.logout')->latest('occurred_at')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->user_id)->toBe($user->id());
});
