<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Queries\GetCourseCurriculumQuery;
use Modules\Academic\Application\Responses\CourseCurriculumResponse;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class GetCourseCurriculumHandler
{
    public function __construct(
        private CourseRepository $courses,
    ) {}

    public function handle(GetCourseCurriculumQuery $query): CourseCurriculumResponse
    {
        $course = $this->courses->findById(CourseId::fromString($query->courseId));

        if ($course === null) {
            throw CourseNotFound::withId($query->courseId);
        }

        return CourseCurriculumResponse::fromCourse($course);
    }
}
