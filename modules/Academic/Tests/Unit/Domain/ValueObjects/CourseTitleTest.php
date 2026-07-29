<?php

declare(strict_types=1);

use Modules\Academic\Domain\ValueObjects\CourseTitle;

it('crea y normaliza el título de un curso', function (): void {
    $title = CourseTitle::fromString('  Introducción a la seguridad vial  ');

    expect($title->value())
        ->toBe('Introducción a la seguridad vial');
});

it('rechaza un título vacío', function (): void {
    CourseTitle::fromString(' ');
})->throws(
    InvalidArgumentException::class,
    'El título del curso no puede estar vacío.',
);
