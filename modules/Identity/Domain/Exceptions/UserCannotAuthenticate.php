<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class UserCannotAuthenticate extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'La cuenta todavía no está habilitada para iniciar sesión.',
            errorCode: 'USER_CANNOT_AUTHENTICATE',
            statusCode: 403,
        );
    }
}
