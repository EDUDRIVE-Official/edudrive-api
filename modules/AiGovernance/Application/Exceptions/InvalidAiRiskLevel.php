<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidAiRiskLevel extends DomainException
{
    public static function withValue(string $value): self
    {
        return new self(
            message: "El nivel de riesgo {$value} no es valido.",
            errorCode: 'INVALID_AI_RISK_LEVEL',
            statusCode: 422,
        );
    }
}
