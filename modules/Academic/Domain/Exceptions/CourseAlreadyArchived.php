<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use DomainException;

final class CourseAlreadyArchived extends DomainException
{
    public static function create(): self
    {
        return new self('El curso ya está archivado.');
    }
}
