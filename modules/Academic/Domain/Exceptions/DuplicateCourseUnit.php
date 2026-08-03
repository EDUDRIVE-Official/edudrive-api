<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class DuplicateCourseUnit extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El UUID de unidad debe ser unico en el curso y su codigo dentro del modulo.',
            errorCode: 'DUPLICATE_COURSE_UNIT',
            statusCode: 422,
        );
    }
}
