<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ExamAttemptLimitReached extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Ya existe un intento activo o se alcanzó el máximo de intentos para este examen.',
            errorCode: 'EXAM_ATTEMPT_LIMIT_REACHED',
            statusCode: 409,
        );
    }
}
