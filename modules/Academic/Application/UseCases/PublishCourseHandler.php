<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\PublishCourseCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Responses\PublishCourseResponse;
use Modules\Academic\Application\Services\CourseSnapshotBuilder;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Entities\CourseVersion;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\CourseVersionRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\UnitContentCoverage;

final readonly class PublishCourseHandler
{
    public function __construct(
        private CourseRepository $courses,
        private CourseVersionRepository $versions,
        private CourseSnapshotBuilder $snapshotBuilder,
    ) {}

    public function handle(
        PublishCourseCommand $command,
    ): PublishCourseResponse {
        $courseId = CourseId::fromString($command->courseId);

        $course = $this->courses->updateAtomicallyWithContentCoverage(
            $courseId,
            function (Course $course, UnitContentCoverage $coverage): void {
                $course->publish(new DateTimeImmutable, $coverage);

                $publishedAt = $course->publishedAt();

                assert($publishedAt !== null);

                $this->versions->save(CourseVersion::create(
                    id: (string) Str::uuid(),
                    courseId: $course->id(),
                    versionNumber: $this->versions->nextVersionNumber($course->id()),
                    snapshot: $this->snapshotBuilder->build($course),
                    publishedAt: $publishedAt,
                ));
            },
        );

        if ($course === null) {
            throw CourseNotFound::withId($command->courseId);
        }

        return PublishCourseResponse::fromCourse($course);
    }
}
