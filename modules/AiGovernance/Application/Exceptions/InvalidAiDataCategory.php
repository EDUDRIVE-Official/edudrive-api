<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidAiDataCategory extends DomainException
{
    public static function withValue(string $value): self
    {
        return new self(
            message: "La categoria de datos {$value} no es valida.",
            errorCode: 'INVALID_AI_DATA_CATEGORY',
            statusCode: 422,
        );
    }
}
