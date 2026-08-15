<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidLessonCompletion extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El tiempo invertido en la leccion no puede ser negativo.',
            errorCode: 'INVALID_LESSON_COMPLETION',
            statusCode: 422,
        );
    }

    public static function duplicateLesson(): self
    {
        return new self(
            message: 'No puede haber mas de una completitud para la misma leccion.',
            errorCode: 'INVALID_LESSON_COMPLETION',
            statusCode: 422,
        );
    }
}
