<?php

declare(strict_types=1);

namespace Modules\Foundation\Application\Bus\Exceptions;

use RuntimeException;

final class MessageHandlerNotFound extends RuntimeException
{
    /**
     * @param  class-string  $messageClass
     */
    public static function forMessage(string $messageClass): self
    {
        return new self(
            sprintf(
                'No se encontró un handler registrado para el mensaje %s.',
                $messageClass,
            ),
        );
    }
}
