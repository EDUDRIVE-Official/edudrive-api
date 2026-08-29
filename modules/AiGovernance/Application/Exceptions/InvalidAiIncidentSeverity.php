<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidAiIncidentSeverity extends DomainException
{
    public static function withValue(string $value): self
    {
        return new self(
            message: "La severidad {$value} no es valida.",
            errorCode: 'INVALID_AI_INCIDENT_SEVERITY',
            statusCode: 422,
        );
    }
}
