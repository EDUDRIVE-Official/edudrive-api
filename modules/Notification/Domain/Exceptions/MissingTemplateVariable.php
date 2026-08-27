<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class MissingTemplateVariable extends DomainException
{
    public static function named(string $variable): self
    {
        return new self(
            message: "Falta el valor de la variable \"{$variable}\" requerida por la plantilla.",
            errorCode: 'MISSING_TEMPLATE_VARIABLE',
            statusCode: 422,
        );
    }
}
