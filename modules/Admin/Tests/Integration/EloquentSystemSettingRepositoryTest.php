<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\Domain\Aggregates\SystemSetting;
use Modules\Admin\Domain\Repositories\SystemSettingRepository;
use Modules\Admin\Domain\ValueObjects\SystemSettingKey;

uses(RefreshDatabase::class);

it('guarda y recupera una configuracion por clave', function (): void {
    $setting = SystemSetting::set(
        key: SystemSettingKey::fromString('maintenance_mode'),
        value: 'false',
        changedAt: new DateTimeImmutable('2026-08-27T10:00:00+00:00'),
    );

    app(SystemSettingRepository::class)->save($setting);
    $found = app(SystemSettingRepository::class)->findByKey(SystemSettingKey::fromString('maintenance_mode'));

    expect($found)->not->toBeNull()
        ->and($found?->value())->toBe('false');
});

it('actualiza el valor de una configuracion existente', function (): void {
    $repository = app(SystemSettingRepository::class);
    $key = SystemSettingKey::fromString('maintenance_mode');
    $setting = SystemSetting::set($key, 'false', new DateTimeImmutable('2026-08-27T10:00:00+00:00'));
    $repository->save($setting);

    $setting->updateValue('true', new DateTimeImmutable('2026-08-27T12:00:00+00:00'));
    $repository->save($setting);

    $found = $repository->findByKey($key);

    expect($found?->value())->toBe('true');
});

it('lista todas las configuraciones registradas', function (): void {
    $repository = app(SystemSettingRepository::class);
    $repository->save(SystemSetting::set(SystemSettingKey::fromString('maintenance_mode'), 'false'));
    $repository->save(SystemSetting::set(SystemSettingKey::fromString('signup_enabled'), 'true'));

    expect($repository->all())->toHaveCount(2);
});

it('no encuentra una configuracion inexistente', function (): void {
    expect(app(SystemSettingRepository::class)->findByKey(SystemSettingKey::fromString('inexistente')))->toBeNull();
});
