<?php

declare(strict_types=1);

use Modules\Academic\Domain\ValueObjects\CourseCode;

it('normaliza el código de un curso', function (): void {
    $code = CourseCode::fromString(' edu-001 ');

    expect($code->value())->toBe('EDU-001');
});

it('rechaza un código vacío', function (): void {
    CourseCode::fromString(' ');
})->throws(
    InvalidArgumentException::class,
    'El código del curso no puede estar vacío.',
);

it('rechaza caracteres no permitidos en el código', function (): void {
    CourseCode::fromString('EDU_001');
})->throws(
    InvalidArgumentException::class,
    'El código del curso solo puede contener letras, números y guiones intermedios.',
);
