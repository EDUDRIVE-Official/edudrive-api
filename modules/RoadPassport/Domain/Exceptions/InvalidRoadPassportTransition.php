<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidRoadPassportTransition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La transicion de estado del pasaporte vial no es valida.',
            errorCode: 'INVALID_ROAD_PASSPORT_TRANSITION',
            statusCode: 422,
        );
    }
}
