<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Mobile\Domain\Aggregates\MobileDevice;
use Modules\Mobile\Domain\Enums\DevicePlatform;
use Modules\Mobile\Domain\ValueObjects\MobileDeviceId;

function newMobileDevice(?string $pushToken = 'push-token-1'): MobileDevice
{
    return MobileDevice::register(
        id: MobileDeviceId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
        deviceId: 'device-abc-123',
        platform: DevicePlatform::Ios,
        pushToken: $pushToken,
        appVersion: '1.0.0',
        lastSeenAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    );
}

it('se registra con la fecha de ultima actividad y de creacion iguales por defecto', function (): void {
    $device = newMobileDevice();

    expect($device->deviceId())->toBe('device-abc-123')
        ->and($device->platform())->toBe(DevicePlatform::Ios)
        ->and($device->appVersion())->toBe('1.0.0')
        ->and($device->hasPushToken())->toBeTrue()
        ->and($device->createdAt())->toBe($device->lastSeenAt());
});

it('acepta registrarse sin push token', function (): void {
    $device = newMobileDevice(null);

    expect($device->pushToken())->toBeNull()
        ->and($device->hasPushToken())->toBeFalse();
});

it('actualiza plataforma, push token, version y ultima actividad al re-registrarse', function (): void {
    $device = newMobileDevice('push-token-viejo');
    $originalCreatedAt = $device->createdAt();

    $device->updateRegistration(
        platform: DevicePlatform::Android,
        pushToken: 'push-token-nuevo',
        appVersion: '1.1.0',
        at: new DateTimeImmutable('2026-08-30T00:00:00+00:00'),
    );

    expect($device->platform())->toBe(DevicePlatform::Android)
        ->and($device->pushToken())->toBe('push-token-nuevo')
        ->and($device->appVersion())->toBe('1.1.0')
        ->and($device->lastSeenAt())->toEqual(new DateTimeImmutable('2026-08-30T00:00:00+00:00'))
        ->and($device->createdAt())->toBe($originalCreatedAt);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = MobileDeviceId::fromString((string) Str::uuid());
    $createdAt = new DateTimeImmutable('2026-08-29T10:00:00+00:00');
    $lastSeenAt = new DateTimeImmutable('2026-08-30T00:00:00+00:00');

    $device = MobileDevice::restore(
        id: $id,
        userId: 'user-1',
        deviceId: 'device-xyz',
        platform: DevicePlatform::Android,
        pushToken: 'push-token',
        appVersion: '2.0.0',
        lastSeenAt: $lastSeenAt,
        createdAt: $createdAt,
    );

    expect($device->id()->equals($id))->toBeTrue()
        ->and($device->userId())->toBe('user-1')
        ->and($device->deviceId())->toBe('device-xyz')
        ->and($device->platform())->toBe(DevicePlatform::Android)
        ->and($device->pushToken())->toBe('push-token')
        ->and($device->appVersion())->toBe('2.0.0')
        ->and($device->lastSeenAt())->toBe($lastSeenAt)
        ->and($device->createdAt())->toBe($createdAt);
});
