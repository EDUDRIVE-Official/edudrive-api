<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidSimulationSessionTransition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La transicion de estado de la sesion de simulacion no es valida.',
            errorCode: 'INVALID_SIMULATION_SESSION_TRANSITION',
            statusCode: 422,
        );
    }
}
