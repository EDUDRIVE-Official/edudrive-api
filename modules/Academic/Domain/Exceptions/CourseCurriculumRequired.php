<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseCurriculumRequired extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El curso requiere al menos un modulo para ser publicado.',
            errorCode: 'COURSE_CURRICULUM_REQUIRED',
            statusCode: 422,
        );
    }
}
