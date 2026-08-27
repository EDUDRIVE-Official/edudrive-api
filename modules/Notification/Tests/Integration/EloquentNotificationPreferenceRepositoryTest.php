<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Notification\Domain\Aggregates\NotificationPreference;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Enums\NotificationFrequency;
use Modules\Notification\Domain\Repositories\NotificationPreferenceRepository;

uses(RefreshDatabase::class);

function persistedPreferenceUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de preferencias',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

it('guarda y recupera una preferencia con sus valores por defecto', function (): void {
    $userId = persistedPreferenceUserId();
    $preference = NotificationPreference::default($userId);

    app(NotificationPreferenceRepository::class)->save($preference);
    $found = app(NotificationPreferenceRepository::class)->findByUserId($userId);

    expect($found)->not->toBeNull()
        ->and($found?->allowedChannels())->toBe(NotificationChannel::cases())
        ->and($found?->mutedCategories())->toBe([])
        ->and($found?->frequency())->toBe(NotificationFrequency::Immediate)
        ->and($found?->consentGiven())->toBeTrue();
});

it('guarda y recupera una preferencia actualizada con horario de silencio', function (): void {
    $userId = persistedPreferenceUserId();
    $preference = NotificationPreference::default($userId);
    $preference->update(
        allowedChannels: [NotificationChannel::Email],
        mutedCategories: ['logro'],
        frequency: NotificationFrequency::Weekly,
        quietHoursStart: '22:00',
        quietHoursEnd: '07:00',
    );

    app(NotificationPreferenceRepository::class)->save($preference);
    $found = app(NotificationPreferenceRepository::class)->findByUserId($userId);

    expect($found?->allowedChannels())->toBe([NotificationChannel::Email])
        ->and($found?->mutedCategories())->toBe(['logro'])
        ->and($found?->frequency())->toBe(NotificationFrequency::Weekly)
        ->and($found?->quietHoursStart())->toBe('22:00')
        ->and($found?->quietHoursEnd())->toBe('07:00');
});

it('guarda y recupera el consentimiento revocado con su fecha', function (): void {
    $userId = persistedPreferenceUserId();
    $preference = NotificationPreference::default($userId);
    $preference->revokeConsent(new DateTimeImmutable('2026-08-26T10:00:00+00:00'));

    app(NotificationPreferenceRepository::class)->save($preference);
    $found = app(NotificationPreferenceRepository::class)->findByUserId($userId);

    expect($found?->consentGiven())->toBeFalse()
        ->and($found?->consentUpdatedAt())->not->toBeNull();
});

it('no encuentra una preferencia inexistente', function (): void {
    expect(app(NotificationPreferenceRepository::class)->findByUserId((string) Str::uuid()))->toBeNull();
});
