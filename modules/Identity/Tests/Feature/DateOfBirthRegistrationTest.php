<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Identity\Domain\Repositories\UserRepository;
use Tests\TestCase;

it('registra opcionalmente la fecha de nacimiento', function (): void {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Usuario menor',
        'email' => sprintf('%s@edudrive.cr', Str::uuid()),
        'password' => 'clave-valida-123',
        'date_of_birth' => '2015-08-28',
    ])->assertCreated();

    $user = app(UserRepository::class)->findById((string) $response->json('data.id'));

    expect($user?->dateOfBirth())->not->toBeNull()
        ->and($user?->isMinor(new DateTimeImmutable('2026-08-28')))->toBeTrue();
});

it('registra sin fecha de nacimiento sin considerar al usuario menor', function (): void {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Usuario sin fecha',
        'email' => sprintf('%s@edudrive.cr', Str::uuid()),
        'password' => 'clave-valida-123',
    ])->assertCreated();

    $user = app(UserRepository::class)->findById((string) $response->json('data.id'));

    expect($user?->dateOfBirth())->toBeNull()
        ->and($user?->isMinor())->toBeFalse();
});

it('rechaza una fecha de nacimiento futura', function (): void {
    /** @var TestCase $this */
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Usuario invalido',
        'email' => sprintf('%s@edudrive.cr', Str::uuid()),
        'password' => 'clave-valida-123',
        'date_of_birth' => now()->addYear()->toDateString(),
    ])->assertStatus(422);
});
