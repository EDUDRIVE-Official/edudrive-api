<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ProgramAlreadyPublished extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El programa ya esta publicado.',
            errorCode: 'PROGRAM_ALREADY_PUBLISHED',
            statusCode: 422,
        );
    }
}
