<?php

declare(strict_types=1);

use Modules\Academic\Domain\ValueObjects\CurriculumCode;

it('normaliza un codigo curricular', function (): void {
    $code = CurriculumCode::fromString(' mod-01 ');

    expect($code->value())
        ->toBe('MOD-01')
        ->and((string) $code)
        ->toBe('MOD-01')
        ->and($code->equals(CurriculumCode::fromString('MOD-01')))
        ->toBeTrue();
});

it('rechaza codigos curriculares vacios o con formato invalido', function (string $code): void {
    CurriculumCode::fromString($code);
})->with([' ', 'MOD_01', '-MOD', 'MOD-'])->throws(InvalidArgumentException::class);

it('rechaza codigos curriculares mayores a sesenta caracteres', function (): void {
    CurriculumCode::fromString(str_repeat('A', 61));
})->throws(InvalidArgumentException::class);
