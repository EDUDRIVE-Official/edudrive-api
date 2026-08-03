<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Foundation\Domain\Exceptions\DomainException;

final class CompetencyNotFound extends DomainException
{
    public static function forId(CompetencyId $id): self
    {
        return new self(
            message: sprintf('No existe la competencia %s.', $id->value()),
            errorCode: 'COMPETENCY_NOT_FOUND',
            statusCode: 404,
        );
    }
}
