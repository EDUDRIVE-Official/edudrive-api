<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class AiDecisionNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: "No se encontro la decision de IA {$id}.",
            errorCode: 'AI_DECISION_NOT_FOUND',
            statusCode: 404,
        );
    }
}
