<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidAiPromptTransition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La transicion de estado del prompt no es valida.',
            errorCode: 'INVALID_AI_PROMPT_TRANSITION',
            statusCode: 422,
        );
    }
}
