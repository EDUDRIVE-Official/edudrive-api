<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Queries\ListCourseVersionsQuery;
use Modules\Academic\Application\Responses\CourseVersionListItemResponse;
use Modules\Academic\Domain\Entities\CourseVersion;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\CourseVersionRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class ListCourseVersionsHandler
{
    public function __construct(
        private CourseRepository $courses,
        private CourseVersionRepository $versions,
    ) {}

    /**
     * @return list<CourseVersionListItemResponse>
     */
    public function handle(
        ListCourseVersionsQuery $query,
    ): array {
        $courseId = CourseId::fromString($query->courseId);

        if ($this->courses->findById($courseId) === null) {
            throw CourseNotFound::withId($query->courseId);
        }

        return array_map(
            static fn (CourseVersion $version): CourseVersionListItemResponse => CourseVersionListItemResponse::fromVersion($version),
            $this->versions->allForCourse($courseId),
        );
    }
}
