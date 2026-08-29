<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidAiModelTransition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La transicion de estado del modelo de IA no es valida.',
            errorCode: 'INVALID_AI_MODEL_TRANSITION',
            statusCode: 422,
        );
    }
}
