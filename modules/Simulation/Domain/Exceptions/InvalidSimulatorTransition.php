<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidSimulatorTransition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La transicion de estado del simulador no es valida.',
            errorCode: 'INVALID_SIMULATOR_TRANSITION',
            statusCode: 422,
        );
    }
}
