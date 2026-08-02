<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: sprintf('No existe un curso con el identificador %s.', $id),
            errorCode: 'COURSE_NOT_FOUND',
            statusCode: 404,
        );
    }
}
