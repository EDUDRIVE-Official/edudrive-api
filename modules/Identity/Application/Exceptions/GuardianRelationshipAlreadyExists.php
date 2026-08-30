<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class GuardianRelationshipAlreadyExists extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Ya existe una relación activa entre este tutor y este menor.',
            errorCode: 'GUARDIAN_RELATIONSHIP_ALREADY_EXISTS',
            statusCode: 409,
        );
    }
}
