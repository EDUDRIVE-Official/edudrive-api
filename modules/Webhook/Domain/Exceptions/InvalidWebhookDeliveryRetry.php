<?php

declare(strict_types=1);

namespace Modules\Webhook\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidWebhookDeliveryRetry extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Solo se puede reintentar manualmente una entrega fallida o en dead-letter.',
            errorCode: 'INVALID_WEBHOOK_DELIVERY_RETRY',
            statusCode: 422,
        );
    }
}
