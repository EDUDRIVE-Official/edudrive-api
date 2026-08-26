<?php

declare(strict_types=1);

namespace Modules\Certification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CertificateAlreadyExists extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El usuario ya tiene un certificado para este curso.',
            errorCode: 'CERTIFICATE_ALREADY_EXISTS',
            statusCode: 409,
        );
    }
}
