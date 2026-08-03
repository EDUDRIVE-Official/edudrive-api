<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidProgramAgeRange extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La edad minima no puede superar la edad maxima.',
            errorCode: 'INVALID_PROGRAM_AGE_RANGE',
            statusCode: 422,
        );
    }

    public static function exceedsMaximum(int $maximum): self
    {
        return new self(
            message: sprintf('Las edades del programa no pueden superar %d.', $maximum),
            errorCode: 'INVALID_PROGRAM_AGE_RANGE',
            statusCode: 422,
        );
    }
}
