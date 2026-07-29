<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Exceptions;

use RuntimeException;

final class EmailAlreadyExists extends RuntimeException
{
    public static function withEmail(string $email): self
    {
        return new self(
            sprintf(
                'Ya existe un usuario registrado con el correo "%s".',
                $email,
            ),
        );
    }
}
