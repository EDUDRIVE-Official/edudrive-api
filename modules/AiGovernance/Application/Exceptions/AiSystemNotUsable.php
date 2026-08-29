<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class AiSystemNotUsable extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: "El sistema de IA {$id} no esta en piloto ni en produccion, no puede invocarse.",
            errorCode: 'AI_SYSTEM_NOT_USABLE',
            statusCode: 422,
        );
    }
}
