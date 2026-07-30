<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\ListCoursesQuery;
use Modules\Academic\Application\Responses\CourseListItemResponse;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;

final readonly class ListCoursesHandler
{
    public function __construct(
        private CourseRepository $courses,
    ) {}

    /**
     * @return list<CourseListItemResponse>
     */
    public function handle(
        ListCoursesQuery $query,
    ): array {
        return array_map(
            static fn (Course $course): CourseListItemResponse => CourseListItemResponse::fromCourse($course),
            $this->courses->all(),
        );
    }
}
