<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseCurriculumIdConflict extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Un identificador curricular ya pertenece a otro curso.',
            errorCode: 'COURSE_CURRICULUM_ID_CONFLICT',
            statusCode: 409,
        );
    }
}
