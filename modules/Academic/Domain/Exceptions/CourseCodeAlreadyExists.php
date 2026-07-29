<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use DomainException;
use Modules\Academic\Domain\ValueObjects\CourseCode;

final class CourseCodeAlreadyExists extends DomainException
{
    public static function forCode(CourseCode $code): self
    {
        return new self(
            sprintf(
                'Ya existe un curso con el código %s.',
                $code->value(),
            ),
        );
    }
}
