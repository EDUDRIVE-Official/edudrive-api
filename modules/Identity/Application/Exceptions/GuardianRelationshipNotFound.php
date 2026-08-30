<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class GuardianRelationshipNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'La relación tutor-menor indicada no existe.',
            errorCode: 'GUARDIAN_RELATIONSHIP_NOT_FOUND',
            statusCode: 404,
        );
    }
}
