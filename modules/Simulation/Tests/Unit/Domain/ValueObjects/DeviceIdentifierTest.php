<?php

declare(strict_types=1);

use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;

it('acepta un identificador valido y recorta espacios', function (): void {
    $identifier = DeviceIdentifier::fromString('  SIM-00042  ');

    expect($identifier->value())->toBe('SIM-00042');
});

it('rechaza un identificador vacio', function (): void {
    expect(fn () => DeviceIdentifier::fromString('   '))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza un identificador que supera 100 caracteres', function (): void {
    expect(fn () => DeviceIdentifier::fromString(str_repeat('A', 101)))
        ->toThrow(InvalidArgumentException::class);
});

it('acepta un identificador de exactamente 100 caracteres', function (): void {
    $identifier = DeviceIdentifier::fromString(str_repeat('A', 100));

    expect($identifier->value())->toHaveLength(100);
});

it('compara identificadores por su valor', function (): void {
    $a = DeviceIdentifier::fromString('SIM-00042');
    $b = DeviceIdentifier::fromString('SIM-00042');
    $c = DeviceIdentifier::fromString('SIM-00043');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
