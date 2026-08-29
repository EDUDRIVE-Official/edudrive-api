<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidAiSystemStatus extends DomainException
{
    public static function withValue(string $value): self
    {
        return new self(
            message: "El estado {$value} no es valido.",
            errorCode: 'INVALID_AI_SYSTEM_STATUS',
            statusCode: 422,
        );
    }
}
