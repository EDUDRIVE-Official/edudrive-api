<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class AiSystemNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: "No se encontro el sistema de IA {$id}.",
            errorCode: 'AI_SYSTEM_NOT_FOUND',
            statusCode: 404,
        );
    }
}
