<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class GuardianRelationshipRequiresMinor extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Solo se puede vincular un tutor a un usuario menor de edad.',
            errorCode: 'GUARDIAN_RELATIONSHIP_REQUIRES_MINOR',
            statusCode: 422,
        );
    }
}
