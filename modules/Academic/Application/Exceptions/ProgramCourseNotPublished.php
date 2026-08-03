<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ProgramCourseNotPublished extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: sprintf('El curso %s debe estar publicado para publicar el programa.', $id),
            errorCode: 'PROGRAM_COURSE_NOT_PUBLISHED',
            statusCode: 422,
        );
    }
}
