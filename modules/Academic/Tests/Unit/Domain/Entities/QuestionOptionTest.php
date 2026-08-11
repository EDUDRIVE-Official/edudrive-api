<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Exceptions\InvalidQuestion;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;

it('crea una opcion de pregunta normalizada', function (): void {
    $option = QuestionOption::create(
        'opt-1',
        QuestionOptionId::fromString((string) Str::uuid()),
        1,
        '  Respuesta correcta  ',
    );

    expect($option->refId())->toBe('opt-1')
        ->and($option->label())->toBe('Respuesta correcta')
        ->and($option->position())->toBe(1)
        ->and($option->side())->toBeNull();
});

it('rechaza una opcion con texto vacio', function (): void {
    expect(fn () => QuestionOption::create('opt-1', QuestionOptionId::fromString((string) Str::uuid()), 1, '  '))
        ->toThrow(InvalidQuestion::class);
});

it('rechaza una posicion no positiva', function (): void {
    expect(fn () => QuestionOption::create('opt-1', QuestionOptionId::fromString((string) Str::uuid()), 0, 'opt'))
        ->toThrow(InvalidQuestion::class);
});

it('rechaza un lado no permitido para asociacion', function (): void {
    expect(fn () => QuestionOption::create('opt-1', QuestionOptionId::fromString((string) Str::uuid()), 1, 'opt', 'center'))
        ->toThrow(InvalidQuestion::class);
});

it('crea una opcion con lado izquierdo para emparejamiento', function (): void {
    $option = QuestionOption::create(
        'left-1',
        QuestionOptionId::fromString((string) Str::uuid()),
        1,
        'Columna izquierda',
        'left',
    );

    expect($option->side())->toBe('left');
});
