<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class SimulatorNotAvailable extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El simulador no esta activo y no puede recibir sesiones nuevas.',
            errorCode: 'SIMULATOR_NOT_AVAILABLE',
            statusCode: 422,
        );
    }
}
