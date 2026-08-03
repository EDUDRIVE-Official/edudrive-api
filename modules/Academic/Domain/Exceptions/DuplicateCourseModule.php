<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class DuplicateCourseModule extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El UUID y el codigo de cada modulo deben ser unicos dentro del curso.',
            errorCode: 'DUPLICATE_COURSE_MODULE',
            statusCode: 422,
        );
    }
}
