<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ProgramAlreadyArchived extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El programa ya esta archivado.',
            errorCode: 'PROGRAM_ALREADY_ARCHIVED',
            statusCode: 422,
        );
    }
}
