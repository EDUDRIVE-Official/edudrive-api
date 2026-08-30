<?php

declare(strict_types=1);

use Modules\Academic\Domain\ValueObjects\GroupId;

it('crea un identificador de grupo a partir de un uuid valido', function (): void {
    $id = GroupId::fromString('01981a64-8300-7b1d-b442-764ea7f92111');

    expect($id->value())->toBe('01981a64-8300-7b1d-b442-764ea7f92111');
});

it('normaliza mayusculas y espacios', function (): void {
    $id = GroupId::fromString('  01981A64-8300-7B1D-B442-764EA7F92111  ');

    expect($id->value())->toBe('01981a64-8300-7b1d-b442-764ea7f92111');
});

it('rechaza un identificador que no es un uuid valido', function (): void {
    GroupId::fromString('no-es-un-uuid');
})->throws(InvalidArgumentException::class);

it('compara identificadores por su valor', function (): void {
    $a = GroupId::fromString('01981a64-8300-7b1d-b442-764ea7f92111');
    $b = GroupId::fromString('01981a64-8300-7b1d-b442-764ea7f92111');
    $c = GroupId::fromString('01981a64-8300-7b1d-b442-764ea7f92112');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
