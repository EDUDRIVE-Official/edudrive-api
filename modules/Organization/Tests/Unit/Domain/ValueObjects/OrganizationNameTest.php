<?php

declare(strict_types=1);

use Modules\Organization\Domain\ValueObjects\OrganizationName;

it('normaliza el nombre de una organización', function (): void {
    $name = OrganizationName::fromString('  Escuela de Manejo EDUDRIVE  ');

    expect($name->value())->toBe('Escuela de Manejo EDUDRIVE');
});

it('rechaza un nombre vacío', function (): void {
    OrganizationName::fromString('   ');
})->throws(
    InvalidArgumentException::class,
    'El nombre de la organización no puede estar vacío.',
);

it('rechaza un nombre demasiado largo', function (): void {
    OrganizationName::fromString(str_repeat('a', 181));
})->throws(
    InvalidArgumentException::class,
    'El nombre de la organización no puede superar 180 caracteres.',
);
