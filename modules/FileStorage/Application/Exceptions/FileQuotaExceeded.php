<?php

declare(strict_types=1);

namespace Modules\FileStorage\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class FileQuotaExceeded extends DomainException
{
    public static function forOwner(string $ownerId, int $limitBytes): self
    {
        return new self(
            message: "El usuario {$ownerId} alcanzo su cuota de almacenamiento de {$limitBytes} bytes.",
            errorCode: 'FILE_QUOTA_EXCEEDED',
            statusCode: 422,
        );
    }
}
