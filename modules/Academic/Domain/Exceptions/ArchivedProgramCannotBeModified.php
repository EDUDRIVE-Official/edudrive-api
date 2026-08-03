<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ArchivedProgramCannotBeModified extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Un programa archivado no puede ser modificado.',
            errorCode: 'ARCHIVED_PROGRAM_CANNOT_BE_MODIFIED',
            statusCode: 422,
        );
    }
}
