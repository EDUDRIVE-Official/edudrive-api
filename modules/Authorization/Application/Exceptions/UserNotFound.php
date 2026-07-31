<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class UserNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: sprintf('No existe un usuario con el identificador %s.', $id),
            errorCode: 'USER_NOT_FOUND',
            statusCode: 404,
        );
    }
}
