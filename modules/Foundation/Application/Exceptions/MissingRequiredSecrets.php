<?php

declare(strict_types=1);

namespace Modules\Foundation\Application\Exceptions;

use RuntimeException;

final class MissingRequiredSecrets extends RuntimeException
{
    /** @param list<string> $missing */
    public static function forKeys(array $missing): self
    {
        return new self(sprintf(
            'Faltan variables de entorno requeridas: %s.',
            implode(', ', $missing),
        ));
    }
}
