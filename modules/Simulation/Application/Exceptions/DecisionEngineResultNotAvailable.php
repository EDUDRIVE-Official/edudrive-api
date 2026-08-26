<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class DecisionEngineResultNotAvailable extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El resultado del motor de decisiones aun no esta disponible; la sesion no ha finalizado.',
            errorCode: 'DECISION_ENGINE_RESULT_NOT_AVAILABLE',
            statusCode: 422,
        );
    }
}
