<?php

declare(strict_types=1);

use Modules\Gamification\Domain\ValueObjects\BadgeCode;

it('normaliza a mayusculas y acepta un codigo valido', function (): void {
    $code = BadgeCode::fromString('  conductor-defensivo  ');

    expect($code->value())->toBe('CONDUCTOR-DEFENSIVO');
});

it('rechaza un codigo vacio', function (): void {
    expect(fn () => BadgeCode::fromString('   '))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza un codigo con caracteres invalidos', function (): void {
    expect(fn () => BadgeCode::fromString('insignia invalida!'))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza un codigo que supera 50 caracteres', function (): void {
    expect(fn () => BadgeCode::fromString(str_repeat('A', 51)))
        ->toThrow(InvalidArgumentException::class);
});

it('compara codigos por su valor', function (): void {
    $a = BadgeCode::fromString('insignia-a');
    $b = BadgeCode::fromString('INSIGNIA-A');
    $c = BadgeCode::fromString('insignia-b');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
