<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Notification\Infrastructure\Jobs\SendEmailNotificationJob;
use Tests\TestCase;

function captureEmailVerificationToken(string $email): string
{
    $token = null;

    Queue::assertPushed(
        SendEmailNotificationJob::class,
        function (SendEmailNotificationJob $job) use ($email, &$token): bool {
            $user = app(UserRepository::class)->findById($job->userId);

            if ($user === null || $user->email()->value() !== $email) {
                return false;
            }

            preg_match('/código de verificación es: (\S+)/u', $job->body, $matches);
            $token = $matches[1] ?? null;

            return $token !== null;
        },
    );

    expect($token)->toBeString();

    return $token;
}

it('envia un correo de verificacion automaticamente al registrarse', function (): void {
    /** @var TestCase $this */
    Queue::fake();
    $email = sprintf('%s@edudrive.cr', Str::uuid());

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Usuario de verificacion',
        'email' => $email,
        'password' => 'clave-valida-123',
    ])->assertCreated();

    captureEmailVerificationToken($email);
});

it('verifica el correo con un token valido y permite iniciar sesion despues', function (): void {
    /** @var TestCase $this */
    Queue::fake();
    $email = sprintf('%s@edudrive.cr', Str::uuid());

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Usuario de verificacion',
        'email' => $email,
        'password' => 'clave-valida-123',
    ])->assertCreated();

    $token = captureEmailVerificationToken($email);

    $this->postJson('/api/v1/auth/verify-email', [
        'email' => $email,
        'token' => $token,
    ])->assertOk();

    $this->postJson('/api/v1/auth/login', [
        'email' => $email,
        'password' => 'clave-valida-123',
    ])->assertOk();
});

it('rechaza un login antes de verificar el correo', function (): void {
    /** @var TestCase $this */
    Queue::fake();
    $email = sprintf('%s@edudrive.cr', Str::uuid());

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Usuario de verificacion',
        'email' => $email,
        'password' => 'clave-valida-123',
    ])->assertCreated();

    $this->postJson('/api/v1/auth/login', [
        'email' => $email,
        'password' => 'clave-valida-123',
    ])
        ->assertStatus(403)
        ->assertJsonPath('code', 'USER_CANNOT_AUTHENTICATE');
});

it('rechaza verificar con un token invalido', function (): void {
    /** @var TestCase $this */
    Queue::fake();
    $email = sprintf('%s@edudrive.cr', Str::uuid());

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Usuario de verificacion',
        'email' => $email,
        'password' => 'clave-valida-123',
    ])->assertCreated();

    $this->postJson('/api/v1/auth/verify-email', [
        'email' => $email,
        'token' => 'token-que-nunca-se-genero',
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_EMAIL_VERIFICATION_TOKEN');
});

it('reenvia el correo de verificacion y el nuevo token invalida el anterior', function (): void {
    /** @var TestCase $this */
    Queue::fake();
    $email = sprintf('%s@edudrive.cr', Str::uuid());

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Usuario de verificacion',
        'email' => $email,
        'password' => 'clave-valida-123',
    ])->assertCreated();

    $firstToken = captureEmailVerificationToken($email);

    $this->postJson('/api/v1/auth/resend-verification', ['email' => $email])->assertOk();

    Queue::assertPushed(SendEmailNotificationJob::class, 2);

    $this->postJson('/api/v1/auth/verify-email', [
        'email' => $email,
        'token' => $firstToken,
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_EMAIL_VERIFICATION_TOKEN');
});

it('responde el mismo mensaje generico al reenviar exista o no el correo', function (): void {
    /** @var TestCase $this */
    Queue::fake();
    $email = sprintf('%s@edudrive.cr', Str::uuid());

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Usuario de verificacion',
        'email' => $email,
        'password' => 'clave-valida-123',
    ])->assertCreated();

    $existing = $this->postJson('/api/v1/auth/resend-verification', ['email' => $email]);
    $nonExisting = $this->postJson('/api/v1/auth/resend-verification', ['email' => 'no-existe@edudrive.cr']);

    $existing->assertOk();
    $nonExisting->assertOk();
    expect($existing->json('message'))->toBe($nonExisting->json('message'));
});
