<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidLessonPosition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Las posiciones de las lecciones deben ser consecutivas e iniciar en uno.',
            errorCode: 'INVALID_LESSON_POSITION',
            statusCode: 422,
        );
    }
}
