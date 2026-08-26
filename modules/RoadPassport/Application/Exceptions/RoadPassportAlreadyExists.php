<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class RoadPassportAlreadyExists extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El usuario ya tiene un pasaporte vial emitido.',
            errorCode: 'ROAD_PASSPORT_ALREADY_EXISTS',
            statusCode: 409,
        );
    }
}
