<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidRoadPassportLevel extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El nivel del pasaporte vial no es valido.',
            errorCode: 'INVALID_ROAD_PASSPORT_LEVEL',
            statusCode: 422,
        );
    }
}
