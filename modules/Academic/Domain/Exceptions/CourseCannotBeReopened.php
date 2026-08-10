<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseCannotBeReopened extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Solo un curso publicado puede reabrirse.',
            errorCode: 'COURSE_CANNOT_BE_REOPENED',
            statusCode: 422,
        );
    }
}
