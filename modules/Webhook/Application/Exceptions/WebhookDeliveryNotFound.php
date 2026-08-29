<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class WebhookDeliveryNotFound extends DomainException
{
    public static function withId(string $deliveryId): self
    {
        return new self(
            message: "No se encontro la entrega {$deliveryId}.",
            errorCode: 'WEBHOOK_DELIVERY_NOT_FOUND',
            statusCode: 404,
        );
    }
}
