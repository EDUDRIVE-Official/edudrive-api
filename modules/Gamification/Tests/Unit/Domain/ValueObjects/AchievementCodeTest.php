<?php

declare(strict_types=1);

use Modules\Gamification\Domain\ValueObjects\AchievementCode;

it('normaliza a mayusculas y acepta un codigo valido', function (): void {
    $code = AchievementCode::fromString('  primer-curso-completado  ');

    expect($code->value())->toBe('PRIMER-CURSO-COMPLETADO');
});

it('rechaza un codigo vacio', function (): void {
    expect(fn () => AchievementCode::fromString('   '))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza un codigo con caracteres invalidos', function (): void {
    expect(fn () => AchievementCode::fromString('logro invalido!'))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza un codigo que supera 50 caracteres', function (): void {
    expect(fn () => AchievementCode::fromString(str_repeat('A', 51)))
        ->toThrow(InvalidArgumentException::class);
});

it('compara codigos por su valor', function (): void {
    $a = AchievementCode::fromString('logro-a');
    $b = AchievementCode::fromString('LOGRO-A');
    $c = AchievementCode::fromString('logro-b');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
