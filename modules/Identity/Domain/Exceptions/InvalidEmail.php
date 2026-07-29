<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Exceptions;

use InvalidArgumentException;

final class InvalidEmail extends InvalidArgumentException
{
    public static function fromValue(string $value): self
    {
        return new self(
            sprintf('El correo electrónico "%s" no tiene un formato válido.', $value),
        );
    }
}
