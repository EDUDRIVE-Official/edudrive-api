<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class OrganizationNotAccessible extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'No tiene acceso a una o más de las organizaciones solicitadas.',
            errorCode: 'ORGANIZATION_NOT_ACCESSIBLE',
            statusCode: 403,
        );
    }
}
