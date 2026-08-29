<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class WebhookSubscriptionNotFound extends DomainException
{
    public static function withId(string $subscriptionId): self
    {
        return new self(
            message: "No se encontro la suscripcion {$subscriptionId}.",
            errorCode: 'WEBHOOK_SUBSCRIPTION_NOT_FOUND',
            statusCode: 404,
        );
    }
}
