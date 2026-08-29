<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Notification\Infrastructure\Jobs\SendEmailNotificationJob;
use Tests\TestCase;

function registerPasswordResetTestUser(string $password = 'clave-original-123'): User
{
    $hasher = app(PasswordHasher::class);
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de recuperacion',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash($password),
    );
    $user->activate(new DateTimeImmutable);
    app(UserRepository::class)->save($user);

    return $user;
}

function capturePasswordResetToken(string $email): string
{
    $token = null;

    Queue::assertPushed(
        SendEmailNotificationJob::class,
        function (SendEmailNotificationJob $job) use ($email, &$token): bool {
            $user = app(UserRepository::class)->findById($job->userId);

            if ($user === null || $user->email()->value() !== $email) {
                return false;
            }

            preg_match('/código de recuperación es: (\S+)/u', $job->body, $matches);
            $token = $matches[1] ?? null;

            return $token !== null;
        },
    );

    expect($token)->toBeString();

    return $token;
}

it('solicita recuperacion de contrasena y encola el correo con el token', function (): void {
    /** @var TestCase $this */
    Queue::fake();
    $user = registerPasswordResetTestUser();

    $this->postJson('/api/v1/auth/forgot-password', [
        'email' => $user->email()->value(),
    ])->assertOk();

    capturePasswordResetToken($user->email()->value());
});

it('responde el mismo mensaje generico exista o no el correo', function (): void {
    /** @var TestCase $this */
    Queue::fake();
    $user = registerPasswordResetTestUser();

    $existing = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => $user->email()->value(),
    ]);

    $nonExisting = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'no-existe@edudrive.cr',
    ]);

    $existing->assertOk();
    $nonExisting->assertOk();
    expect($existing->json('message'))->toBe($nonExisting->json('message'));

    Queue::assertPushed(SendEmailNotificationJob::class, 1);
});

it('restablece la contrasena con un token valido y permite iniciar sesion con la nueva clave', function (): void {
    /** @var TestCase $this */
    Queue::fake();
    $user = registerPasswordResetTestUser();

    $this->postJson('/api/v1/auth/forgot-password', [
        'email' => $user->email()->value(),
    ])->assertOk();

    $token = capturePasswordResetToken($user->email()->value());

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email()->value(),
        'token' => $token,
        'password' => 'clave-nueva-456',
        'password_confirmation' => 'clave-nueva-456',
    ])->assertOk();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-nueva-456',
    ])->assertOk();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-original-123',
    ])->assertStatus(401);
});

it('invalida las sesiones anteriores al restablecer la contrasena', function (): void {
    /** @var TestCase $this */
    Queue::fake();
    $user = registerPasswordResetTestUser();

    $previousToken = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-original-123',
    ])->json('data.token.access_token');

    $this->postJson('/api/v1/auth/forgot-password', [
        'email' => $user->email()->value(),
    ])->assertOk();

    $resetToken = capturePasswordResetToken($user->email()->value());

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email()->value(),
        'token' => $resetToken,
        'password' => 'clave-nueva-456',
        'password_confirmation' => 'clave-nueva-456',
    ])->assertOk();

    Auth::forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$previousToken)
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});

it('rechaza restablecer con un token invalido', function (): void {
    /** @var TestCase $this */
    $user = registerPasswordResetTestUser();

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email()->value(),
        'token' => 'token-que-nunca-se-solicito',
        'password' => 'clave-nueva-456',
        'password_confirmation' => 'clave-nueva-456',
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_PASSWORD_RESET_TOKEN');
});

it('rechaza restablecer sin confirmacion de contrasena', function (): void {
    /** @var TestCase $this */
    $user = registerPasswordResetTestUser();

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email()->value(),
        'token' => 'cualquier-token',
        'password' => 'clave-nueva-456',
    ])->assertJsonValidationErrors('password');
});
