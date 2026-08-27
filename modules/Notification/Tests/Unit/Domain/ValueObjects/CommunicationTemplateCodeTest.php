<?php

declare(strict_types=1);

use Modules\Notification\Domain\ValueObjects\CommunicationTemplateCode;

it('normaliza a mayusculas y acepta un codigo valido', function (): void {
    $code = CommunicationTemplateCode::fromString('  bienvenida-email  ');

    expect($code->value())->toBe('BIENVENIDA-EMAIL');
});

it('rechaza un codigo vacio', function (): void {
    expect(fn () => CommunicationTemplateCode::fromString('   '))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza un codigo con caracteres invalidos', function (): void {
    expect(fn () => CommunicationTemplateCode::fromString('plantilla invalida!'))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza un codigo que supera 50 caracteres', function (): void {
    expect(fn () => CommunicationTemplateCode::fromString(str_repeat('A', 51)))
        ->toThrow(InvalidArgumentException::class);
});

it('compara codigos por su valor', function (): void {
    $a = CommunicationTemplateCode::fromString('plantilla-a');
    $b = CommunicationTemplateCode::fromString('PLANTILLA-A');
    $c = CommunicationTemplateCode::fromString('plantilla-b');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
