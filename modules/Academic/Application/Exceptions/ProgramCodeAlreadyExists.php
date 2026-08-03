<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Academic\Domain\ValueObjects\ProgramCode;
use Modules\Foundation\Domain\Exceptions\DomainException;

final class ProgramCodeAlreadyExists extends DomainException
{
    public static function forCode(ProgramCode $code): self
    {
        return new self(
            message: sprintf('Ya existe un programa con el código %s.', $code->value()),
            errorCode: 'PROGRAM_CODE_ALREADY_EXISTS',
            statusCode: 409,
        );
    }
}
