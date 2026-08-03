<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseModuleRequiresUnits extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Cada modulo requiere al menos una unidad para publicar el curso.',
            errorCode: 'COURSE_MODULE_REQUIRES_UNITS',
            statusCode: 422,
        );
    }
}
