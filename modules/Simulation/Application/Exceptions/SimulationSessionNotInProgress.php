<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class SimulationSessionNotInProgress extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La sesion de simulacion no esta en curso.',
            errorCode: 'SIMULATION_SESSION_NOT_IN_PROGRESS',
            statusCode: 422,
        );
    }
}
