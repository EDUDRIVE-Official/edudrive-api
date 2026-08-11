<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidQuestionScore extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El puntaje de la pregunta debe ser un entero positivo.',
            errorCode: 'INVALID_QUESTION_SCORE',
            statusCode: 422,
        );
    }
}