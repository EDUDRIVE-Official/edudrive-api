<?php

declare(strict_types=1);

namespace Modules\AsyncProcessing\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class AsyncJobNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: "No se encontro el trabajo asincrono {$id}.",
            errorCode: 'ASYNC_JOB_NOT_FOUND',
            statusCode: 404,
        );
    }
}
