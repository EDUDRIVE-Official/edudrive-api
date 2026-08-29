<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class AiPromptNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: "No se encontro el prompt {$id}.",
            errorCode: 'AI_PROMPT_NOT_FOUND',
            statusCode: 404,
        );
    }
}
