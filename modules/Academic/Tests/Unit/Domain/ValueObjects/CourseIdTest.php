<?php

declare(strict_types=1);

use Modules\Academic\Domain\ValueObjects\CourseId;

it('crea un identificador de curso válido', function (): void {
    $id = CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0');

    expect($id->value())
        ->toBe('01981a64-8300-7b1d-b442-764ea7f915c0');
});

it('rechaza un identificador de curso inválido', function (): void {
    CourseId::fromString('identificador-invalido');
})->throws(
    InvalidArgumentException::class,
    'El identificador del curso debe ser un UUID válido.',
);
