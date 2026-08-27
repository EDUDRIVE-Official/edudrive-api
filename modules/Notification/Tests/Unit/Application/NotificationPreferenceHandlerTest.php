<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Notification\Application\Commands\GiveNotificationConsentCommand;
use Modules\Notification\Application\Commands\RevokeNotificationConsentCommand;
use Modules\Notification\Application\Commands\UpdateNotificationPreferenceCommand;
use Modules\Notification\Application\Queries\GetMyNotificationPreferenceQuery;
use Modules\Notification\Application\Responses\NotificationPreferenceResponse;
use Modules\Notification\Application\UseCases\GetMyNotificationPreferenceHandler;
use Modules\Notification\Application\UseCases\GiveNotificationConsentHandler;
use Modules\Notification\Application\UseCases\RevokeNotificationConsentHandler;
use Modules\Notification\Application\UseCases\UpdateNotificationPreferenceHandler;

it('devuelve valores por defecto cuando el usuario no tiene preferencia registrada', function (): void {
    $preferences = new InMemoryNotificationPreferenceRepository;

    $response = (new GetMyNotificationPreferenceHandler($preferences))->handle(new GetMyNotificationPreferenceQuery((string) Str::uuid()));

    expect($response)->toBeInstanceOf(NotificationPreferenceResponse::class)
        ->and($response->allowedChannels)->toHaveCount(4)
        ->and($response->mutedCategories)->toBe([])
        ->and($response->frequency)->toBe('immediate')
        ->and($response->consentGiven)->toBeTrue();
});

it('actualiza la preferencia de un usuario sin registro previo', function (): void {
    $preferences = new InMemoryNotificationPreferenceRepository;
    $userId = (string) Str::uuid();

    $response = (new UpdateNotificationPreferenceHandler($preferences))->handle(new UpdateNotificationPreferenceCommand(
        userId: $userId,
        allowedChannels: ['email', 'web'],
        mutedCategories: ['logro'],
        frequency: 'weekly',
        quietHoursStart: '22:00',
        quietHoursEnd: '07:00',
    ));

    expect($response->allowedChannels)->toBe(['email', 'web'])
        ->and($response->mutedCategories)->toBe(['logro'])
        ->and($response->frequency)->toBe('weekly')
        ->and($response->quietHoursStart)->toBe('22:00')
        ->and($response->quietHoursEnd)->toBe('07:00');
});

it('otorga el consentimiento y registra la fecha', function (): void {
    $preferences = new InMemoryNotificationPreferenceRepository;
    $userId = (string) Str::uuid();
    (new RevokeNotificationConsentHandler($preferences))->handle(new RevokeNotificationConsentCommand($userId));

    $response = (new GiveNotificationConsentHandler($preferences))->handle(new GiveNotificationConsentCommand($userId));

    expect($response->consentGiven)->toBeTrue()
        ->and($response->consentUpdatedAt)->not->toBeNull();
});

it('revoca el consentimiento y registra la fecha', function (): void {
    $preferences = new InMemoryNotificationPreferenceRepository;
    $userId = (string) Str::uuid();

    $response = (new RevokeNotificationConsentHandler($preferences))->handle(new RevokeNotificationConsentCommand($userId));

    expect($response->consentGiven)->toBeFalse()
        ->and($response->consentUpdatedAt)->not->toBeNull();
});
