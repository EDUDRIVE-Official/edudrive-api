<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class RoadPassportNotFound extends DomainException
{
    public static function withId(string $roadPassportId): self
    {
        return new self(
            message: "No se encontro el pasaporte vial {$roadPassportId}.",
            errorCode: 'ROAD_PASSPORT_NOT_FOUND',
            statusCode: 404,
        );
    }

    public static function forUser(): self
    {
        return new self(
            message: 'El usuario no tiene un pasaporte vial emitido.',
            errorCode: 'ROAD_PASSPORT_NOT_FOUND',
            statusCode: 404,
        );
    }
}
