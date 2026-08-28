<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Services;

use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class ReportCourseResolver
{
    public function __construct(private CourseRepository $courses) {}

    /**
     * @param  list<string>  $courseIds
     * @return list<Course>
     */
    public function resolve(array $courseIds): array
    {
        if ($courseIds === []) {
            return $this->courses->all();
        }

        return array_map(function (string $courseId): Course {
            $course = $this->courses->findById(CourseId::fromString($courseId));

            if ($course === null) {
                throw CourseNotFound::withId($courseId);
            }

            return $course;
        }, $courseIds);
    }
}
