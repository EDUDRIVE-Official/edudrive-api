<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class AiSystemRequiresCommitteeApproval extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Un sistema de IA clasificado como IA-3 o IA-4 requiere aprobacion del Comite de Gobierno de IA antes de pasar a produccion.',
            errorCode: 'AI_SYSTEM_REQUIRES_COMMITTEE_APPROVAL',
            statusCode: 422,
        );
    }
}
