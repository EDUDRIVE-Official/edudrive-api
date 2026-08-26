<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class SimulatorAlreadyExists extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Ya existe un simulador registrado con ese identificador de dispositivo.',
            errorCode: 'SIMULATOR_ALREADY_EXISTS',
            statusCode: 409,
        );
    }
}
