<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use RuntimeException;
use Modules\Academic\Domain\ValueObjects\CompetencyId;

final class CompetencyNotFound extends RuntimeException
{
    public static function forId(CompetencyId $id): self
    {
        return new self(sprintf('No existe la competencia %s.', $id->value()));
    }
}
