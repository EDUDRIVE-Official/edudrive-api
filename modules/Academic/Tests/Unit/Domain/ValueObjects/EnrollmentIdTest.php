<?php

declare(strict_types=1);

use Modules\Academic\Domain\ValueObjects\EnrollmentId;

it('normaliza un enrollment id valido', function (): void {
    $id = EnrollmentId::fromString(' 01981A64-8300-7B1D-B442-764EA7F915C0 ');

    expect($id->value())->toBe('01981a64-8300-7b1d-b442-764ea7f915c0')
        ->and((string) $id)->toBe('01981a64-8300-7b1d-b442-764ea7f915c0');
});

it('rechaza un enrollment id invalido', function (): void {
    expect(fn () => EnrollmentId::fromString('invalido'))
        ->toThrow(InvalidArgumentException::class);
});
