<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Mobile\Domain\Aggregates\MobileDevice;
use Modules\Mobile\Domain\Enums\DevicePlatform;
use Modules\Mobile\Domain\Repositories\MobileDeviceRepository;
use Modules\Mobile\Domain\ValueObjects\MobileDeviceId;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedMobilePushRecipientId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Destinatario de push',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

it('envia un push real al dispositivo del usuario cuando se crea una notificacion con canal mobile', function (): void {
    /** @var TestCase $this */
    $recipientId = persistedMobilePushRecipientId();
    $device = MobileDevice::register(
        id: MobileDeviceId::fromString((string) Str::uuid()),
        userId: $recipientId,
        deviceId: 'device-1',
        platform: DevicePlatform::Ios,
        pushToken: 'push-token-abc',
        appVersion: '1.0.0',
    );
    app(MobileDeviceRepository::class)->save($device);

    Http::fake(['fcm.googleapis.com/*' => Http::response(['success' => 1], 200)]);
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/notification/notifications', [
        'user_id' => $recipientId,
        'channel' => 'mobile',
        'category' => 'logro',
        'subject' => 'Nuevo logro',
        'body' => 'Has obtenido un logro nuevo.',
    ])->assertCreated();

    Http::assertSent(function (HttpClientRequest $request): bool {
        return $request['to'] === 'push-token-abc'
            && $request['notification']['title'] === 'Nuevo logro'
            && $request['notification']['body'] === 'Has obtenido un logro nuevo.';
    });
});

it('no envia ningun push cuando el usuario no tiene dispositivos con token registrado', function (): void {
    /** @var TestCase $this */
    $recipientId = persistedMobilePushRecipientId();
    Http::fake();
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/notification/notifications', [
        'user_id' => $recipientId,
        'channel' => 'mobile',
        'category' => 'logro',
        'subject' => 'Nuevo logro',
        'body' => 'Has obtenido un logro nuevo.',
    ])->assertCreated();

    Http::assertNothingSent();
});
