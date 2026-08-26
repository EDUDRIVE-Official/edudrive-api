<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class NotificationNotFound extends DomainException
{
    public static function withId(string $notificationId): self
    {
        return new self(
            message: "No se encontro la notificacion {$notificationId}.",
            errorCode: 'NOTIFICATION_NOT_FOUND',
            statusCode: 404,
        );
    }
}
