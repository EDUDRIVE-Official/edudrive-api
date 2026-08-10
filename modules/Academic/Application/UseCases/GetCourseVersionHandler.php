<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Exceptions\CourseVersionNotFound;
use Modules\Academic\Application\Queries\GetCourseVersionQuery;
use Modules\Academic\Application\Responses\CourseVersionResponse;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\CourseVersionRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class GetCourseVersionHandler
{
    public function __construct(
        private CourseRepository $courses,
        private CourseVersionRepository $versions,
    ) {}

    public function handle(
        GetCourseVersionQuery $query,
    ): CourseVersionResponse {
        $courseId = CourseId::fromString($query->courseId);

        if ($this->courses->findById($courseId) === null) {
            throw CourseVersionNotFound::create($query->courseId, $query->versionNumber);
        }

        $version = $this->versions->findByNumber($courseId, $query->versionNumber);

        if ($version === null) {
            throw CourseVersionNotFound::create($query->courseId, $query->versionNumber);
        }

        return CourseVersionResponse::fromVersion($version);
    }
}
