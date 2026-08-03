<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidCurriculumDuration extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La duracion curricular debe ser positiva.',
            errorCode: 'INVALID_CURRICULUM_DURATION',
            statusCode: 422,
        );
    }
}
