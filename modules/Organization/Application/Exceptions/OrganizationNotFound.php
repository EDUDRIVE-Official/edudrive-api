<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class OrganizationNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: sprintf('No existe una organización con el identificador %s.', $id),
            errorCode: 'ORGANIZATION_NOT_FOUND',
            statusCode: 404,
        );
    }
}
