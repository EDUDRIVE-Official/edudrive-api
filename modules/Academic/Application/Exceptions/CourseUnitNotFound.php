<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseUnitNotFound extends DomainException
{
    public static function create(): self
    {
        return new self('La unidad del curso no existe.', 'COURSE_UNIT_NOT_FOUND', 404);
    }
}
