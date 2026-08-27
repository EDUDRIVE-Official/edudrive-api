<?php

declare(strict_types=1);

use Modules\Admin\Domain\Aggregates\SystemSetting;
use Modules\Admin\Domain\ValueObjects\SystemSettingKey;

it('se establece con su valor y fecha de cambio', function (): void {
    $setting = SystemSetting::set(
        key: SystemSettingKey::fromString('maintenance_mode'),
        value: 'false',
        changedAt: new DateTimeImmutable('2026-08-27T10:00:00+00:00'),
    );

    expect($setting->key()->value())->toBe('maintenance_mode')
        ->and($setting->value())->toBe('false');
});

it('actualiza su valor y fecha de cambio', function (): void {
    $setting = SystemSetting::set(
        key: SystemSettingKey::fromString('maintenance_mode'),
        value: 'false',
        changedAt: new DateTimeImmutable('2026-08-27T10:00:00+00:00'),
    );

    $changedAt = new DateTimeImmutable('2026-08-27T12:00:00+00:00');
    $setting->updateValue('true', $changedAt);

    expect($setting->value())->toBe('true')
        ->and($setting->changedAt())->toBe($changedAt);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $key = SystemSettingKey::fromString('maintenance_mode');
    $changedAt = new DateTimeImmutable('2026-08-27T10:00:00+00:00');

    $setting = SystemSetting::restore($key, 'true', $changedAt);

    expect($setting->key()->equals($key))->toBeTrue()
        ->and($setting->value())->toBe('true')
        ->and($setting->changedAt())->toBe($changedAt);
});
