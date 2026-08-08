<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseContentIdConflict extends DomainException
{
    public static function create(): self
    {
        return new self('Un identificador de contenido ya pertenece a otra unidad.', 'COURSE_CONTENT_ID_CONFLICT', 409);
    }
}
