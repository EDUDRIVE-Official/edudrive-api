<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class AiSystemRequiresHumanSupervisionForMinors extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Un sistema de IA que procesa datos de menores requiere un nivel de supervision humana de al menos "Propone" antes de pasar a produccion.',
            errorCode: 'AI_SYSTEM_REQUIRES_HUMAN_SUPERVISION_FOR_MINORS',
            statusCode: 422,
        );
    }
}
