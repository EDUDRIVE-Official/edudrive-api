<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseCurriculumCannotBeModified extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El curriculo solo puede modificarse mientras el curso esta en borrador.',
            errorCode: 'COURSE_CURRICULUM_CANNOT_BE_MODIFIED',
            statusCode: 422,
        );
    }
}
