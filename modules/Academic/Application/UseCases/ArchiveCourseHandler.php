<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Modules\Academic\Application\Commands\ArchiveCourseCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Responses\ArchiveCourseResponse;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class ArchiveCourseHandler
{
    public function __construct(
        private CourseRepository $courses,
    ) {}

    public function handle(
        ArchiveCourseCommand $command,
    ): ArchiveCourseResponse {
        $courseId = CourseId::fromString($command->courseId);

        $course = $this->courses->updateAtomically(
            $courseId,
            static function (Course $course): void {
                $course->archive(new DateTimeImmutable);
            },
        );

        if ($course === null) {
            throw CourseNotFound::withId($command->courseId);
        }

        return ArchiveCourseResponse::fromCourse($course);
    }
}
