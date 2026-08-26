<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidNotificationTransition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La notificacion ya se encuentra leida.',
            errorCode: 'INVALID_NOTIFICATION_TRANSITION',
            statusCode: 422,
        );
    }
}
