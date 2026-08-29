<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
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

function persistedMobileDeviceOtherUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Otro usuario',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function persistedMobileDeviceFeature(string $userId): MobileDevice
{
    $device = MobileDevice::register(
        id: MobileDeviceId::fromString((string) Str::uuid()),
        userId: $userId,
        deviceId: 'device-'.strtoupper((string) Str::random(6)),
        platform: DevicePlatform::Ios,
        pushToken: 'push-token-1',
        appVersion: '1.0.0',
    );
    app(MobileDeviceRepository::class)->save($device);

    return $device;
}

it('registra un dispositivo para el usuario autenticado', function (): void {
    /** @var TestCase $this */
    $user = actingAsRole(Role::Student);

    $this->postJson('/api/v1/mobile/devices', [
        'device_id' => 'device-1',
        'platform' => 'ios',
        'push_token' => 'push-token-1',
        'app_version' => '1.0.0',
    ])
        ->assertCreated()
        ->assertJsonPath('data.device_id', 'device-1')
        ->assertJsonPath('data.platform', 'ios')
        ->assertJsonPath('data.has_push_token', true);

    expect(app(MobileDeviceRepository::class)->findByUserAndDeviceId((string) $user->id, 'device-1'))->not->toBeNull();
});

it('rechaza registrar un dispositivo con una plataforma invalida', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);

    $this->postJson('/api/v1/mobile/devices', [
        'device_id' => 'device-1',
        'platform' => 'windows-phone',
        'app_version' => '1.0.0',
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_DEVICE_PLATFORM');
});

it('lista los dispositivos propios sin exponer los de otro usuario', function (): void {
    /** @var TestCase $this */
    $user = actingAsRole(Role::Student);
    persistedMobileDeviceFeature((string) $user->id);
    persistedMobileDeviceFeature(persistedMobileDeviceOtherUserId());

    $this->getJson('/api/v1/mobile/devices')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('elimina un dispositivo propio', function (): void {
    /** @var TestCase $this */
    $user = actingAsRole(Role::Student);
    $device = persistedMobileDeviceFeature((string) $user->id);

    $this->deleteJson("/api/v1/mobile/devices/{$device->deviceId()}")
        ->assertOk()
        ->assertJsonPath('data.deleted', true);

    expect(app(MobileDeviceRepository::class)->findById($device->id()))->toBeNull();
});

it('rechaza eliminar un dispositivo de otro usuario', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $device = persistedMobileDeviceFeature(persistedMobileDeviceOtherUserId());

    $this->deleteJson("/api/v1/mobile/devices/{$device->deviceId()}")
        ->assertStatus(404);
});

it('requiere autenticacion para todos los endpoints protegidos', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/mobile/devices')->assertUnauthorized();
    $this->postJson('/api/v1/mobile/devices', ['device_id' => 'd', 'platform' => 'ios', 'app_version' => '1.0.0'])->assertUnauthorized();
});
