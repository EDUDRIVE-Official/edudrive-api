<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Mobile\Domain\Aggregates\MobileDevice;
use Modules\Mobile\Domain\Enums\DevicePlatform;
use Modules\Mobile\Domain\Repositories\MobileDeviceRepository;
use Modules\Mobile\Domain\ValueObjects\MobileDeviceId;

uses(RefreshDatabase::class);

function persistedMobileDeviceUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario movil',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function newPersistableMobileDevice(string $userId, ?string $pushToken = 'push-token-1'): MobileDevice
{
    return MobileDevice::register(
        id: MobileDeviceId::fromString((string) Str::uuid()),
        userId: $userId,
        deviceId: 'device-'.strtoupper((string) Str::random(6)),
        platform: DevicePlatform::Ios,
        pushToken: $pushToken,
        appVersion: '1.0.0',
        lastSeenAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    );
}

it('guarda y recupera un dispositivo por identificador', function (): void {
    $userId = persistedMobileDeviceUserId();
    $device = newPersistableMobileDevice($userId);

    app(MobileDeviceRepository::class)->save($device);
    $found = app(MobileDeviceRepository::class)->findById($device->id());

    expect($found)->not->toBeNull()
        ->and($found?->id()->equals($device->id()))->toBeTrue()
        ->and($found?->userId())->toBe($userId)
        ->and($found?->deviceId())->toBe($device->deviceId())
        ->and($found?->platform())->toBe(DevicePlatform::Ios)
        ->and($found?->pushToken())->toBe('push-token-1')
        ->and($found?->appVersion())->toBe('1.0.0');
});

it('encuentra un dispositivo por usuario y device id', function (): void {
    $userId = persistedMobileDeviceUserId();
    $device = newPersistableMobileDevice($userId);
    app(MobileDeviceRepository::class)->save($device);

    $found = app(MobileDeviceRepository::class)->findByUserAndDeviceId($userId, $device->deviceId());

    expect($found?->id()->equals($device->id()))->toBeTrue();
    expect(app(MobileDeviceRepository::class)->findByUserAndDeviceId($userId, 'device-inexistente'))->toBeNull();
});

it('lista todos los dispositivos de un usuario', function (): void {
    $userId = persistedMobileDeviceUserId();
    $repository = app(MobileDeviceRepository::class);
    $repository->save(newPersistableMobileDevice($userId));
    $repository->save(newPersistableMobileDevice($userId));

    expect($repository->findByUser($userId))->toHaveCount(2);
});

it('lista solo los dispositivos con push token registrado', function (): void {
    $userId = persistedMobileDeviceUserId();
    $repository = app(MobileDeviceRepository::class);
    $repository->save(newPersistableMobileDevice($userId, 'push-token-a'));
    $repository->save(newPersistableMobileDevice($userId, null));

    expect($repository->findWithPushTokenByUser($userId))->toHaveCount(1);
});

it('actualiza un dispositivo existente en vez de duplicarlo al guardar de nuevo', function (): void {
    $userId = persistedMobileDeviceUserId();
    $device = newPersistableMobileDevice($userId);
    $repository = app(MobileDeviceRepository::class);
    $repository->save($device);

    $device->updateRegistration(DevicePlatform::Android, 'push-token-nuevo', '1.1.0', new DateTimeImmutable('now'));
    $repository->save($device);

    $found = $repository->findById($device->id());
    expect($found?->platform())->toBe(DevicePlatform::Android)
        ->and($found?->pushToken())->toBe('push-token-nuevo')
        ->and($repository->findByUser($userId))->toHaveCount(1);
});

it('elimina un dispositivo', function (): void {
    $userId = persistedMobileDeviceUserId();
    $device = newPersistableMobileDevice($userId);
    $repository = app(MobileDeviceRepository::class);
    $repository->save($device);

    $repository->delete($device->id());

    expect($repository->findById($device->id()))->toBeNull();
});
