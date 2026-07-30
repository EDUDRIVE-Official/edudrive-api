<?php

declare(strict_types=1);

use Modules\Organization\Domain\Entities\Campus;

it('crea una sede con nombre normalizado', function (): void {
    $campus = Campus::create(
        id: '01981a64-8300-7b1d-b442-764ea7f915c0',
        name: '  Sede Cabo Velas  ',
    );

    expect($campus->id())->toBe('01981a64-8300-7b1d-b442-764ea7f915c0')
        ->and($campus->name())->toBe('Sede Cabo Velas');
});

it('rechaza una sede sin nombre', function (): void {
    Campus::create(id: '01981a64-8300-7b1d-b442-764ea7f915c0', name: '   ');
})->throws(
    InvalidArgumentException::class,
    'El nombre de la sede no puede estar vacío.',
);

it('rechaza una sede con nombre demasiado largo', function (): void {
    Campus::create(id: '01981a64-8300-7b1d-b442-764ea7f915c0', name: str_repeat('a', 181));
})->throws(
    InvalidArgumentException::class,
    'El nombre de la sede no puede superar 180 caracteres.',
);

it('renombra una sede con nombre normalizado', function (): void {
    $campus = Campus::create(
        id: '01981a64-8300-7b1d-b442-764ea7f915c0',
        name: 'Sede Cabo Velas',
    );

    $campus->rename('  Sede Tempisque  ');

    expect($campus->name())->toBe('Sede Tempisque');
});
