<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class UserNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'El usuario solicitado no existe.',
            errorCode: 'USER_NOT_FOUND',
            statusCode: 404,
        );
    }
}
