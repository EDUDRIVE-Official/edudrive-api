<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseAlreadyArchived extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El curso ya está archivado.',
            errorCode: 'COURSE_ALREADY_ARCHIVED',
            statusCode: 422,
        );
    }
}
