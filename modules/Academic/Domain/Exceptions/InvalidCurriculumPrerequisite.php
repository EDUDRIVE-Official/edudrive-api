<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidCurriculumPrerequisite extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Los prerrequisitos deben ser unicos y referenciar elementos curriculares anteriores.',
            errorCode: 'INVALID_CURRICULUM_PREREQUISITE',
            statusCode: 422,
        );
    }
}
