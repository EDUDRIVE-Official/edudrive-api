<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class SystemSettingNotFound extends DomainException
{
    public static function withKey(string $key): self
    {
        return new self(
            message: "No se encontro la configuracion {$key}.",
            errorCode: 'SYSTEM_SETTING_NOT_FOUND',
            statusCode: 404,
        );
    }
}
