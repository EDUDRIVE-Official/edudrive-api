<?php

declare(strict_types=1);

namespace Modules\Certification\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidCertificateTransition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La transicion de estado del certificado no es valida.',
            errorCode: 'INVALID_CERTIFICATE_TRANSITION',
            statusCode: 422,
        );
    }
}
