<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseReviewStateInvalid extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El curso no se encuentra en el estado requerido para esta accion.',
            errorCode: 'COURSE_REVIEW_STATE_INVALID',
            statusCode: 422,
        );
    }
}
