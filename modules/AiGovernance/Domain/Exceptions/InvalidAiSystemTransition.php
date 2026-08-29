<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidAiSystemTransition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La transicion de estado del sistema de IA no es valida.',
            errorCode: 'INVALID_AI_SYSTEM_TRANSITION',
            statusCode: 422,
        );
    }
}
