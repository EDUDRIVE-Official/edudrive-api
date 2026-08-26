<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidTheoryExam extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El examen teorico no es valido.',
            errorCode: 'INVALID_THEORY_EXAM',
            statusCode: 422,
        );
    }
}
