<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidGroupPeriod extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La fecha de fin del periodo lectivo debe ser posterior a la fecha de inicio.',
            errorCode: 'INVALID_GROUP_PERIOD',
            statusCode: 422,
        );
    }
}
