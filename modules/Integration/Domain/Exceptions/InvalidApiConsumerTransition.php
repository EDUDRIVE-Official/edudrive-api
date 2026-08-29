<?php

declare(strict_types=1);

namespace Modules\Integration\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidApiConsumerTransition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La transicion de estado del consumidor no es valida.',
            errorCode: 'INVALID_API_CONSUMER_TRANSITION',
            statusCode: 422,
        );
    }
}
