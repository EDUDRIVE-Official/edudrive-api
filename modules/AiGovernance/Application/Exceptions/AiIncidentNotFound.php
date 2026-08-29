<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class AiIncidentNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: "No se encontro el incidente de IA {$id}.",
            errorCode: 'AI_INCIDENT_NOT_FOUND',
            statusCode: 404,
        );
    }
}
