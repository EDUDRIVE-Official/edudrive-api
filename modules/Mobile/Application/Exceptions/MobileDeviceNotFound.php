<?php

declare(strict_types=1);

namespace Modules\Mobile\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class MobileDeviceNotFound extends DomainException
{
    public static function withDeviceId(string $deviceId): self
    {
        return new self(
            message: "No se encontro el dispositivo {$deviceId}.",
            errorCode: 'MOBILE_DEVICE_NOT_FOUND',
            statusCode: 404,
        );
    }
}
