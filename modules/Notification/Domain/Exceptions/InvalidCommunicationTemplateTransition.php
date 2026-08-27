<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidCommunicationTemplateTransition extends DomainException
{
    public static function alreadyRetired(): self
    {
        return new self(
            message: 'La plantilla ya se encuentra retirada.',
            errorCode: 'INVALID_COMMUNICATION_TEMPLATE_TRANSITION',
            statusCode: 422,
        );
    }

    public static function cannotEditRetired(): self
    {
        return new self(
            message: 'No se puede editar el contenido de una plantilla retirada.',
            errorCode: 'INVALID_COMMUNICATION_TEMPLATE_TRANSITION',
            statusCode: 422,
        );
    }
}
