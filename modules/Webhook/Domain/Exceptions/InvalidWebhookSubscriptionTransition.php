<?php

declare(strict_types=1);

namespace Modules\Webhook\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidWebhookSubscriptionTransition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La transicion de estado de la suscripcion no es valida.',
            errorCode: 'INVALID_WEBHOOK_SUBSCRIPTION_TRANSITION',
            statusCode: 422,
        );
    }
}
