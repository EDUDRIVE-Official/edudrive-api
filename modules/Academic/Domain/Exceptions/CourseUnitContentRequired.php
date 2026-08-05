<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseUnitContentRequired extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Todas las unidades requieren contenido completo antes de publicar el curso.',
            errorCode: 'COURSE_UNIT_CONTENT_REQUIRED',
            statusCode: 422,
        );
    }
}
