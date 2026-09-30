<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ScenarioPracticeIncomplete extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Resuelve correctamente todas las prácticas de decisión antes de completar la lección.',
            errorCode: 'SCENARIO_PRACTICE_INCOMPLETE',
            statusCode: 422,
        );
    }
}
