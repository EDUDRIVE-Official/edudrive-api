<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidCurriculumText extends DomainException
{
    public static function forField(string $field): self
    {
        return new self(
            message: sprintf('El campo curricular %s no es valido.', $field),
            errorCode: 'INVALID_CURRICULUM_TEXT',
            statusCode: 422,
        );
    }
}
