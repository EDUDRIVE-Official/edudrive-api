<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ContentAccessibilityRequired extends DomainException
{
    public static function forField(string $field): self
    {
        return new self(
            message: sprintf('El campo de accesibilidad %s es obligatorio.', $field),
            errorCode: 'CONTENT_ACCESSIBILITY_REQUIRED',
            statusCode: 422,
        );
    }
}
