<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidCredentials extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'The provided credentials are invalid.',
            errorCode: 'INVALID_CREDENTIALS',
            statusCode: 401,
        );
    }
}
