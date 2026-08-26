<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class SimulationSessionNotFound extends DomainException
{
    public static function withId(string $sessionId): self
    {
        return new self(
            message: "No se encontro la sesion de simulacion {$sessionId}.",
            errorCode: 'SIMULATION_SESSION_NOT_FOUND',
            statusCode: 404,
        );
    }
}
