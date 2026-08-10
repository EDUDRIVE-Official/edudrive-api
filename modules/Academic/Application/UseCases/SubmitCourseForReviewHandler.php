<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\SubmitCourseForReviewCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Responses\CourseStatusResponse;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class SubmitCourseForReviewHandler
{
    public function __construct(
        private CourseRepository $courses,
    ) {}

    public function handle(
        SubmitCourseForReviewCommand $command,
    ): CourseStatusResponse {
        $courseId = CourseId::fromString($command->courseId);

        $course = $this->courses->updateAtomically(
            $courseId,
            static function (Course $course): void {
                $course->submitForReview();
            },
        );

        if ($course === null) {
            throw CourseNotFound::withId($command->courseId);
        }

        return CourseStatusResponse::fromCourse($course);
    }
}
