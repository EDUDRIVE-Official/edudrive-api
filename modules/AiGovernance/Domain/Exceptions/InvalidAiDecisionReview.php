<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidAiDecisionReview extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Solo se puede aprobar o rechazar una decision de IA que este pendiente de revision.',
            errorCode: 'INVALID_AI_DECISION_REVIEW',
            statusCode: 422,
        );
    }
}
