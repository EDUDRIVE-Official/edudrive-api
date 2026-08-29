<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidAiProviderEvaluationTransition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La transicion de estado de la evaluacion de proveedor no es valida.',
            errorCode: 'INVALID_AI_PROVIDER_EVALUATION_TRANSITION',
            statusCode: 422,
        );
    }
}
