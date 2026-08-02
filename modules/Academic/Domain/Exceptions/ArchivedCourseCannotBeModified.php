<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ArchivedCourseCannotBeModified extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Un curso archivado no puede ser modificado.',
            errorCode: 'ARCHIVED_COURSE_CANNOT_BE_MODIFIED',
            statusCode: 422,
        );
    }
}
