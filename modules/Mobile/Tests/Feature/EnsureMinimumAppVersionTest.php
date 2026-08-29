<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\Domain\Aggregates\SystemSetting;
use Modules\Admin\Domain\Repositories\SystemSettingRepository;
use Modules\Admin\Domain\ValueObjects\SystemSettingKey;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

function setMobileMinAppVersion(string $version): void
{
    app(SystemSettingRepository::class)->save(
        SystemSetting::set(SystemSettingKey::fromString('mobile_min_app_version'), $version),
    );
}

it('permite el acceso cuando no hay version minima configurada, sin exigir el header', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);

    $this->getJson('/api/v1/mobile/devices')->assertOk();
});

it('rechaza la peticion sin el header X-App-Version cuando hay version minima configurada', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    setMobileMinAppVersion('1.2.0');

    $this->getJson('/api/v1/mobile/devices')
        ->assertStatus(400)
        ->assertJsonPath('code', 'MISSING_APP_VERSION');
});

it('rechaza una version por debajo del minimo configurado', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    setMobileMinAppVersion('1.2.0');

    $this->withHeaders(['X-App-Version' => '1.1.0'])
        ->getJson('/api/v1/mobile/devices')
        ->assertStatus(426)
        ->assertJsonPath('code', 'APP_VERSION_UNSUPPORTED');
});

it('permite una version igual o por encima del minimo configurado', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    setMobileMinAppVersion('1.2.0');

    $this->withHeaders(['X-App-Version' => '1.2.0'])
        ->getJson('/api/v1/mobile/devices')
        ->assertOk();

    $this->withHeaders(['X-App-Version' => '1.3.0'])
        ->getJson('/api/v1/mobile/devices')
        ->assertOk();
});
