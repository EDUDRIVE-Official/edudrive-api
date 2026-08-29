<?php

declare(strict_types=1);

namespace Modules\AsyncProcessing\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidAsyncJobTransition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El trabajo asincrono no puede realizar esa transicion de estado desde su estado actual.',
            errorCode: 'INVALID_ASYNC_JOB_TRANSITION',
            statusCode: 422,
        );
    }
}
