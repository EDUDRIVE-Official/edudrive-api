<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidQuestion extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La pregunta no es válida.',
            errorCode: 'INVALID_QUESTION',
            statusCode: 422,
        );
    }
}