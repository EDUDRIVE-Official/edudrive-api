<?php

declare(strict_types=1);

namespace Modules\Legal\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class GuardianDeclarationRequired extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Se requiere declarar el nombre de la madre, padre o tutor para registrar el consentimiento de un usuario menor de edad.',
            errorCode: 'GUARDIAN_DECLARATION_REQUIRED',
            statusCode: 422,
        );
    }
}
