<?php

declare(strict_types=1);

namespace Modules\FileStorage\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class FileNotFound extends DomainException
{
    public static function withId(string $fileId): self
    {
        return new self(
            message: "No se encontro el archivo {$fileId}.",
            errorCode: 'FILE_NOT_FOUND',
            statusCode: 404,
        );
    }
}
