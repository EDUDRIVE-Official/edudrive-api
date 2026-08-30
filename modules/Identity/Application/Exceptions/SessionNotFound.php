<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class SessionNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'La sesión indicada no existe.',
            errorCode: 'SESSION_NOT_FOUND',
            statusCode: 404,
        );
    }
}
