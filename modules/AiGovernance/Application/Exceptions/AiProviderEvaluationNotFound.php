<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class AiProviderEvaluationNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: "No se encontro la evaluacion de proveedor {$id}.",
            errorCode: 'AI_PROVIDER_EVALUATION_NOT_FOUND',
            statusCode: 404,
        );
    }
}
