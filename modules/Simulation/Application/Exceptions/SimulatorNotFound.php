<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class SimulatorNotFound extends DomainException
{
    public static function withId(string $simulatorId): self
    {
        return new self(
            message: "No se encontro el simulador {$simulatorId}.",
            errorCode: 'SIMULATOR_NOT_FOUND',
            statusCode: 404,
        );
    }
}
