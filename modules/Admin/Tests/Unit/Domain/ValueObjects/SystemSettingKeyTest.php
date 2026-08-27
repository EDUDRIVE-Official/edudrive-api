<?php

declare(strict_types=1);

use Modules\Admin\Domain\ValueObjects\SystemSettingKey;

it('acepta una clave valida en minusculas con guiones bajos', function (): void {
    $key = SystemSettingKey::fromString('  maintenance_mode  ');

    expect($key->value())->toBe('maintenance_mode');
});

it('rechaza una clave que inicia con un numero', function (): void {
    expect(fn () => SystemSettingKey::fromString('1_invalid'))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza una clave con mayusculas', function (): void {
    expect(fn () => SystemSettingKey::fromString('Maintenance_Mode'))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza una clave que supera 100 caracteres', function (): void {
    expect(fn () => SystemSettingKey::fromString('a'.str_repeat('b', 100)))
        ->toThrow(InvalidArgumentException::class);
});

it('compara claves por su valor', function (): void {
    $a = SystemSettingKey::fromString('maintenance_mode');
    $b = SystemSettingKey::fromString('maintenance_mode');
    $c = SystemSettingKey::fromString('other_setting');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
