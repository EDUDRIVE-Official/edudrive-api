<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Notification\Domain\Aggregates\NotificationPreference;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Enums\NotificationFrequency;

it('crea valores por defecto: todo permitido, inmediato, consentimiento otorgado', function (): void {
    $preference = NotificationPreference::default((string) Str::uuid());

    expect($preference->allowedChannels())->toBe(NotificationChannel::cases())
        ->and($preference->mutedCategories())->toBe([])
        ->and($preference->frequency())->toBe(NotificationFrequency::Immediate)
        ->and($preference->quietHoursStart())->toBeNull()
        ->and($preference->quietHoursEnd())->toBeNull()
        ->and($preference->consentGiven())->toBeTrue()
        ->and($preference->consentUpdatedAt())->toBeNull();
});

it('permite un canal y categoria cuando el consentimiento esta otorgado y nada esta silenciado', function (): void {
    $preference = NotificationPreference::default((string) Str::uuid());

    expect($preference->allows(NotificationChannel::Web, 'logro'))->toBeTrue();
});

it('rechaza un canal no permitido', function (): void {
    $preference = NotificationPreference::default((string) Str::uuid());
    $preference->update(
        allowedChannels: [NotificationChannel::Email],
        mutedCategories: [],
        frequency: NotificationFrequency::Immediate,
        quietHoursStart: null,
        quietHoursEnd: null,
    );

    expect($preference->allows(NotificationChannel::Web, 'logro'))->toBeFalse()
        ->and($preference->allows(NotificationChannel::Email, 'logro'))->toBeTrue();
});

it('rechaza una categoria silenciada explicitamente', function (): void {
    $preference = NotificationPreference::default((string) Str::uuid());
    $preference->update(
        allowedChannels: NotificationChannel::cases(),
        mutedCategories: ['logro'],
        frequency: NotificationFrequency::Immediate,
        quietHoursStart: null,
        quietHoursEnd: null,
    );

    expect($preference->allows(NotificationChannel::Web, 'logro'))->toBeFalse()
        ->and($preference->allows(NotificationChannel::Web, 'certificado'))->toBeTrue();
});

it('rechaza todo cuando el consentimiento fue revocado', function (): void {
    $preference = NotificationPreference::default((string) Str::uuid());
    $preference->revokeConsent(new DateTimeImmutable('now'));

    expect($preference->allows(NotificationChannel::Web, 'logro'))->toBeFalse()
        ->and($preference->consentGiven())->toBeFalse();
});

it('vuelve a permitir todo al otorgar el consentimiento de nuevo', function (): void {
    $preference = NotificationPreference::default((string) Str::uuid());
    $preference->revokeConsent(new DateTimeImmutable('now'));
    $preference->giveConsent(new DateTimeImmutable('now'));

    expect($preference->allows(NotificationChannel::Web, 'logro'))->toBeTrue()
        ->and($preference->consentGiven())->toBeTrue()
        ->and($preference->consentUpdatedAt())->not->toBeNull();
});

it('acepta un horario de silencio valido', function (): void {
    $preference = NotificationPreference::default((string) Str::uuid());

    $preference->update(
        allowedChannels: NotificationChannel::cases(),
        mutedCategories: [],
        frequency: NotificationFrequency::Daily,
        quietHoursStart: '22:00',
        quietHoursEnd: '07:00',
    );

    expect($preference->quietHoursStart())->toBe('22:00')
        ->and($preference->quietHoursEnd())->toBe('07:00')
        ->and($preference->frequency())->toBe(NotificationFrequency::Daily);
});

it('rechaza un horario de silencio con solo un extremo', function (): void {
    $preference = NotificationPreference::default((string) Str::uuid());

    expect(fn () => $preference->update(NotificationChannel::cases(), [], NotificationFrequency::Immediate, '22:00', null))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza un horario de silencio con formato invalido', function (): void {
    $preference = NotificationPreference::default((string) Str::uuid());

    expect(fn () => $preference->update(NotificationChannel::cases(), [], NotificationFrequency::Immediate, '25:00', '07:00'))
        ->toThrow(InvalidArgumentException::class);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $userId = (string) Str::uuid();
    $consentUpdatedAt = new DateTimeImmutable('2026-08-26T10:00:00+00:00');

    $preference = NotificationPreference::restore(
        userId: $userId,
        allowedChannels: [NotificationChannel::Email],
        mutedCategories: ['logro'],
        frequency: NotificationFrequency::Weekly,
        quietHoursStart: '22:00',
        quietHoursEnd: '07:00',
        consentGiven: false,
        consentUpdatedAt: $consentUpdatedAt,
    );

    expect($preference->userId())->toBe($userId)
        ->and($preference->allowedChannels())->toBe([NotificationChannel::Email])
        ->and($preference->mutedCategories())->toBe(['logro'])
        ->and($preference->frequency())->toBe(NotificationFrequency::Weekly)
        ->and($preference->quietHoursStart())->toBe('22:00')
        ->and($preference->quietHoursEnd())->toBe('07:00')
        ->and($preference->consentGiven())->toBeFalse()
        ->and($preference->consentUpdatedAt())->toBe($consentUpdatedAt);
});
