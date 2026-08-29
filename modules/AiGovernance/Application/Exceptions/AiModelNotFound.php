<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class AiModelNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: "No se encontro el modelo de IA {$id}.",
            errorCode: 'AI_MODEL_NOT_FOUND',
            statusCode: 404,
        );
    }
}
