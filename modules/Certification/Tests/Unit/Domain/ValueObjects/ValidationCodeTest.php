<?php

declare(strict_types=1);

use Modules\Certification\Domain\ValueObjects\ValidationCode;

it('genera un codigo de validacion con el formato XXXX-XXXX-XXXX', function (): void {
    $code = ValidationCode::generate();

    expect($code->value())->toMatch('/^[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}-[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}-[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}$/');
});

it('genera codigos distintos en cada llamada', function (): void {
    $first = ValidationCode::generate();
    $second = ValidationCode::generate();

    expect($first->value())->not->toBe($second->value());
});

it('normaliza a mayusculas y acepta un codigo valido desde persistencia', function (): void {
    $code = ValidationCode::fromString('ab34-cd67-ef89');

    expect($code->value())->toBe('AB34-CD67-EF89');
});

it('rechaza un codigo con formato invalido', function (): void {
    expect(fn () => ValidationCode::fromString('no-es-un-codigo'))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza caracteres ambiguos como 0 O 1 I', function (): void {
    expect(fn () => ValidationCode::fromString('0000-1111-OOII'))
        ->toThrow(InvalidArgumentException::class);
});

it('compara codigos por su valor', function (): void {
    $code = ValidationCode::fromString('AB34-CD67-EF89');
    $same = ValidationCode::fromString('ab34-cd67-ef89');
    $other = ValidationCode::generate();

    expect($code->equals($same))->toBeTrue()
        ->and($code->equals($other))->toBeFalse();
});
