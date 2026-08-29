<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidApiConsumerScope extends DomainException
{
    public static function withValue(string $scope): self
    {
        return new self(
            message: "El alcance {$scope} no es un alcance externo valido.",
            errorCode: 'INVALID_API_CONSUMER_SCOPE',
            statusCode: 422,
        );
    }
}
