<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CoursePedagogicalQualityRequired extends DomainException
{
    /** @param list<string> $issues */
    public static function withIssues(array $issues): self
    {
        return new self(
            message: 'El curso requiere revisión pedagógica antes de publicarse: '.implode(' · ', array_slice($issues, 0, 5)),
            errorCode: 'COURSE_PEDAGOGICAL_QUALITY_REQUIRED',
            statusCode: 422,
        );
    }
}
