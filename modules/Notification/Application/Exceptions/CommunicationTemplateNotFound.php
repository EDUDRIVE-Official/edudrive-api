<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CommunicationTemplateNotFound extends DomainException
{
    public static function withId(string $templateId): self
    {
        return new self(
            message: "No se encontro la plantilla {$templateId}.",
            errorCode: 'COMMUNICATION_TEMPLATE_NOT_FOUND',
            statusCode: 404,
        );
    }
}
