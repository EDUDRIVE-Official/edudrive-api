<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidContentBlock extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El bloque de contenido no es valido.',
            errorCode: 'INVALID_CONTENT_BLOCK',
            statusCode: 422,
        );
    }
}
