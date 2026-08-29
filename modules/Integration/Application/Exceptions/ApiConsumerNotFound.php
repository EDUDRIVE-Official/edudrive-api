<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ApiConsumerNotFound extends DomainException
{
    public static function withId(string $consumerId): self
    {
        return new self(
            message: "No se encontro el consumidor {$consumerId}.",
            errorCode: 'API_CONSUMER_NOT_FOUND',
            statusCode: 404,
        );
    }
}
