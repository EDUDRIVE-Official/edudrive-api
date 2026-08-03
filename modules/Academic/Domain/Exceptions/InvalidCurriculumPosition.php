<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidCurriculumPosition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Las posiciones curriculares deben ser consecutivas e iniciar en uno.',
            errorCode: 'INVALID_CURRICULUM_POSITION',
            statusCode: 422,
        );
    }
}
