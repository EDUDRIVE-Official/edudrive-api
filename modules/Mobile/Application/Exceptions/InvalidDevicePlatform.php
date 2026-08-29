<?php

declare(strict_types=1);

namespace Modules\Mobile\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidDevicePlatform extends DomainException
{
    public static function withValue(string $platform): self
    {
        return new self(
            message: "La plataforma {$platform} no es valida.",
            errorCode: 'INVALID_DEVICE_PLATFORM',
            statusCode: 422,
        );
    }
}
