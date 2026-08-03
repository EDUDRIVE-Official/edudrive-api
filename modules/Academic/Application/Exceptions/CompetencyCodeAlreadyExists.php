<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use RuntimeException;
use Modules\Academic\Domain\ValueObjects\CompetencyCode;

final class CompetencyCodeAlreadyExists extends RuntimeException
{
    public static function forCode(CompetencyCode $code): self
    {
        return new self(sprintf('Ya existe una competencia con el código %s.', $code->value()));
    }
}
