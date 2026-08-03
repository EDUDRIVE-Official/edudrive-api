<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseNotFoundForProgram extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: sprintf('No existe un curso para el programa con el identificador %s.', $id),
            errorCode: 'PROGRAM_COURSE_NOT_FOUND',
            statusCode: 404,
        );
    }
}
