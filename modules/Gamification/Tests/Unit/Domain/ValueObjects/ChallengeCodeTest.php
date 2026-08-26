<?php

declare(strict_types=1);

use Modules\Gamification\Domain\ValueObjects\ChallengeCode;

it('normaliza a mayusculas y acepta un codigo valido', function (): void {
    $code = ChallengeCode::fromString('  semana-manejo-seguro  ');

    expect($code->value())->toBe('SEMANA-MANEJO-SEGURO');
});

it('rechaza un codigo vacio', function (): void {
    expect(fn () => ChallengeCode::fromString('   '))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza un codigo con caracteres invalidos', function (): void {
    expect(fn () => ChallengeCode::fromString('reto invalido!'))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza un codigo que supera 50 caracteres', function (): void {
    expect(fn () => ChallengeCode::fromString(str_repeat('A', 51)))
        ->toThrow(InvalidArgumentException::class);
});

it('compara codigos por su valor', function (): void {
    $a = ChallengeCode::fromString('reto-a');
    $b = ChallengeCode::fromString('RETO-A');
    $c = ChallengeCode::fromString('reto-b');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
