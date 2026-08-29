<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidAiSupervisionLevel extends DomainException
{
    public static function withValue(int $value): self
    {
        return new self(
            message: "El nivel de supervision {$value} no es valido.",
            errorCode: 'INVALID_AI_SUPERVISION_LEVEL',
            statusCode: 422,
        );
    }
}
