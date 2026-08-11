<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidExam extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El examen no es válido.',
            errorCode: 'INVALID_EXAM',
            statusCode: 422,
        );
    }
}
