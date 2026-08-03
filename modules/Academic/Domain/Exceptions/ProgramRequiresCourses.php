<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ProgramRequiresCourses extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El programa requiere al menos un curso para ser publicado.',
            errorCode: 'PROGRAM_REQUIRES_COURSES',
            statusCode: 422,
        );
    }
}
