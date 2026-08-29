<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidWebhookEventName extends DomainException
{
    public static function withValue(string $eventName): self
    {
        return new self(
            message: "El evento {$eventName} no es un evento de webhook valido.",
            errorCode: 'INVALID_WEBHOOK_EVENT_NAME',
            statusCode: 422,
        );
    }
}
