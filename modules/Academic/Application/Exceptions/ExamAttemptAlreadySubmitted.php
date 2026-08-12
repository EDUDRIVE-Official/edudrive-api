<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ExamAttemptAlreadySubmitted extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Este intento de evaluación ya fue finalizado.',
            errorCode: 'EXAM_ATTEMPT_ALREADY_SUBMITTED',
            statusCode: 409,
        );
    }
}
