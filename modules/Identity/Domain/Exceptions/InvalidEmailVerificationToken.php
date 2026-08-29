<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidEmailVerificationToken extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'El token de verificación de correo es inválido o ha expirado.',
            errorCode: 'INVALID_EMAIL_VERIFICATION_TOKEN',
            statusCode: 422,
        );
    }
}
