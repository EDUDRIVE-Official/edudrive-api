<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Modules\Academic\Application\Commands\PublishProgramCommand;
use Modules\Academic\Application\Exceptions\CourseNotFoundForProgram;
use Modules\Academic\Application\Exceptions\ProgramCourseNotPublished;
use Modules\Academic\Application\Exceptions\ProgramNotFound;
use Modules\Academic\Application\Responses\ProgramResponse;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\ProgramRepository;
use Modules\Academic\Domain\ValueObjects\ProgramId;

final readonly class PublishProgramHandler
{
    public function __construct(
        private ProgramRepository $programs,
        private CourseRepository $courses,
    ) {}

    public function handle(PublishProgramCommand $command): ProgramResponse
    {
        $program = $this->programs->findById(ProgramId::fromString($command->programId));

        if ($program === null) {
            throw ProgramNotFound::withId($command->programId);
        }

        $program->ensureCanBePublished();

        foreach ($program->courses() as $programCourse) {
            $course = $this->courses->findById($programCourse->courseId());

            if ($course === null) {
                throw CourseNotFoundForProgram::withId($programCourse->courseId()->value());
            }

            if (! $course->status()->isPublished()) {
                throw ProgramCourseNotPublished::withId($course->id()->value());
            }
        }

        $program->publish(new DateTimeImmutable);
        $this->programs->save($program);

        return ProgramResponse::fromProgram($program);
    }
}
