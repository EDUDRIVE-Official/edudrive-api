<?php

declare(strict_types=1);

use Modules\Organization\Domain\ValueObjects\OrganizationId;

it('crea un identificador de organización válido', function (): void {
    $id = OrganizationId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0');

    expect($id->value())->toBe('01981a64-8300-7b1d-b442-764ea7f915c0');
});

it('rechaza un identificador de organización inválido', function (): void {
    OrganizationId::fromString('identificador-invalido');
})->throws(
    InvalidArgumentException::class,
    'El identificador de la organización debe ser un UUID válido.',
);
