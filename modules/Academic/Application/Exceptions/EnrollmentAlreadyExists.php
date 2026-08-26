<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class EnrollmentAlreadyExists extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Ya existe una matricula activa o pendiente para este usuario en el curso.',
            errorCode: 'ENROLLMENT_ALREADY_EXISTS',
            statusCode: 409,
        );
    }
}
