<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseVersionNotFound extends DomainException
{
    public static function create(string $courseId, ?int $versionNumber = null): self
    {
        $version = $versionNumber === null ? 'ninguna' : (string) $versionNumber;

        return new self(
            message: sprintf('No existe la version %s del curso %s.', $version, $courseId),
            errorCode: 'COURSE_VERSION_NOT_FOUND',
            statusCode: 404,
        );
    }
}
