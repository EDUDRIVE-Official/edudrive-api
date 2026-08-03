<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class DuplicateProgramCourse extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Un curso no puede aparecer mas de una vez en el programa.',
            errorCode: 'DUPLICATE_PROGRAM_COURSE',
            statusCode: 422,
        );
    }
}
