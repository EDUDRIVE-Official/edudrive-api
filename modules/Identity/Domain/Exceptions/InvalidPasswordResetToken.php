<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidPasswordResetToken extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'El token de recuperación de contraseña es inválido o ha expirado.',
            errorCode: 'INVALID_PASSWORD_RESET_TOKEN',
            statusCode: 422,
        );
    }
}
