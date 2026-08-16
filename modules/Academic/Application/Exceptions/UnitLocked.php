<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class UnitLocked extends DomainException
{
    public static function withId(string $unitId): self
    {
        return new self(
            message: sprintf('La unidad %s todavia esta bloqueada por prerrequisitos pendientes.', $unitId),
            errorCode: 'UNIT_LOCKED',
            statusCode: 422,
        );
    }
}
