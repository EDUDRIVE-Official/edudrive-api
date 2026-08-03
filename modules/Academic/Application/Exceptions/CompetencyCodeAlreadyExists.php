<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Academic\Domain\ValueObjects\CompetencyCode;
use Modules\Foundation\Domain\Exceptions\DomainException;

final class CompetencyCodeAlreadyExists extends DomainException
{
    public static function forCode(CompetencyCode $code): self
    {
        return new self(
            message: sprintf('Ya existe una competencia con el código %s.', $code->value()),
            errorCode: 'COMPETENCY_CODE_ALREADY_EXISTS',
            statusCode: 409,
        );
    }
}
