<?php

declare(strict_types=1);

namespace Modules\Legal\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ConsentAlreadyRevoked extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Este consentimiento ya fue revocado.',
            errorCode: 'CONSENT_ALREADY_REVOKED',
            statusCode: 422,
        );
    }
}
