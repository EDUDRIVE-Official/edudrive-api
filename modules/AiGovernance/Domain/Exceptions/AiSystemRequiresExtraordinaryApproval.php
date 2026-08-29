<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class AiSystemRequiresExtraordinaryApproval extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Un sistema de IA clasificado como IA-4 requiere aprobacion extraordinaria antes de pasar a produccion.',
            errorCode: 'AI_SYSTEM_REQUIRES_EXTRAORDINARY_APPROVAL',
            statusCode: 422,
        );
    }
}
