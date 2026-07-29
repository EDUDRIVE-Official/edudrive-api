<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use DomainException;

final class ArchivedCourseCannotBeModified extends DomainException
{
    public static function create(): self
    {
        return new self('Un curso archivado no puede ser modificado.');
    }
}
