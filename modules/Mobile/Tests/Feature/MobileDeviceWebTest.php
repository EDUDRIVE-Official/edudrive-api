<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Mobile\Domain\Aggregates\MobileDevice;
use Modules\Mobile\Domain\Enums\DevicePlatform;
use Modules\Mobile\Domain\Repositories\MobileDeviceRepository;
use Modules\Mobile\Domain\ValueObjects\MobileDeviceId;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedMobileWebUser(string $name = 'Usuario móvil'): User
{
    $user = User::register(id: (string) Str::uuid(), name: $name, email: Email::fromString(Str::uuid().'@edudrive.cr'), passwordHash: 'hash');
    app(UserRepository::class)->save($user);

    return $user;
}

function persistedMobileWebDevice(string $userId, string $deviceId): MobileDevice
{
    $device = MobileDevice::register(
        id: MobileDeviceId::fromString((string) Str::uuid()),
        userId: $userId,
        deviceId: $deviceId,
        platform: DevicePlatform::Android,
        pushToken: 'push-token',
        appVersion: '1.2.0',
    );
    app(MobileDeviceRepository::class)->save($device);

    return $device;
}

it('requiere autenticación para consultar dispositivos', function (): void {
    /** @var TestCase $this */
    $this->get('/mis-dispositivos')->assertRedirect(route('login'));
});

it('muestra únicamente los dispositivos propios', function (): void {
    /** @var TestCase $this */
    $user = persistedMobileWebUser();
    persistedMobileWebDevice($user->id(), 'telefono-propio');
    $other = persistedMobileWebUser('Otro usuario móvil');
    persistedMobileWebDevice($other->id(), 'telefono-ajeno');
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/mis-dispositivos')
        ->assertOk()
        ->assertSeeText('Dispositivo Android 1')
        ->assertDontSeeText('telefono-propio')
        ->assertDontSeeText('telefono-ajeno');
});

it('permite desvincular un dispositivo propio y rechaza uno ajeno', function (): void {
    /** @var TestCase $this */
    $user = persistedMobileWebUser();
    $own = persistedMobileWebDevice($user->id(), 'telefono-propio');
    $other = persistedMobileWebUser('Propietario ajeno');
    $foreign = persistedMobileWebDevice($other->id(), 'telefono-ajeno');
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->delete('/mis-dispositivos/'.$foreign->deviceId())->assertRedirect()->assertSessionHas('error');
    expect(app(MobileDeviceRepository::class)->findById($foreign->id()))->not->toBeNull();
    $this->delete('/mis-dispositivos/'.$own->deviceId())->assertRedirect()->assertSessionHas('status');
    expect(app(MobileDeviceRepository::class)->findById($own->id()))->toBeNull();
});
