<?php

declare(strict_types=1);

namespace Modules\Legal\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class PolicyNotFound extends DomainException
{
    public static function withKey(string $key): self
    {
        return new self(
            message: "No se encontró la política {$key}.",
            errorCode: 'POLICY_NOT_FOUND',
            statusCode: 404,
        );
    }
}
