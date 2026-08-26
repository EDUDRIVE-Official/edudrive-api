<?php

declare(strict_types=1);

namespace Modules\Certification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CertificateNotFound extends DomainException
{
    public static function withId(string $certificateId): self
    {
        return new self(
            message: "No se encontro el certificado {$certificateId}.",
            errorCode: 'CERTIFICATE_NOT_FOUND',
            statusCode: 404,
        );
    }
}
