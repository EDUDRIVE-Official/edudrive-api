<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidExamAttempt extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El intento de evaluación no es válido.',
            errorCode: 'INVALID_EXAM_ATTEMPT',
            statusCode: 422,
        );
    }
}
