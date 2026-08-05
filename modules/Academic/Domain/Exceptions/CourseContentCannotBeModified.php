<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseContentCannotBeModified extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El contenido del curso solo puede modificarse en estado borrador.',
            errorCode: 'COURSE_CONTENT_CANNOT_BE_MODIFIED',
            statusCode: 422,
        );
    }
}
