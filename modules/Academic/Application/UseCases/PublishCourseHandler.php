<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Modules\Academic\Application\Commands\PublishCourseCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Responses\PublishCourseResponse;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\UnitContentCoverage;

final readonly class PublishCourseHandler
{
    public function __construct(
        private CourseRepository $courses,
    ) {}

    public function handle(
        PublishCourseCommand $command,
    ): PublishCourseResponse {
        $courseId = CourseId::fromString($command->courseId);

        $course = $this->courses->updateAtomicallyWithContentCoverage(
            $courseId,
            static function (Course $course, UnitContentCoverage $coverage): void {
                $course->publish(new DateTimeImmutable, $coverage);
            },
        );

        if ($course === null) {
            throw CourseNotFound::withId($command->courseId);
        }

        return PublishCourseResponse::fromCourse($course);
    }
}
