<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Exceptions;

use InvalidArgumentException;

final class InvalidUserName extends InvalidArgumentException
{
    public static function empty(): self
    {
        return new self('El nombre del usuario no puede estar vacío.');
    }

    public static function tooLong(): self
    {
        return new self('El nombre del usuario no puede superar los 150 caracteres.');
    }
}
