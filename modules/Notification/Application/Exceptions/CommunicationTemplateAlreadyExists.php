<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CommunicationTemplateAlreadyExists extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Ya existe una plantilla registrada con ese codigo e idioma.',
            errorCode: 'COMMUNICATION_TEMPLATE_ALREADY_EXISTS',
            statusCode: 409,
        );
    }
}
