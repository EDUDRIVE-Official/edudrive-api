<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\ReplaceProgramCoursesCommand;
use Modules\Academic\Application\Exceptions\CourseNotFoundForProgram;
use Modules\Academic\Application\Exceptions\ProgramCourseNotPublished;
use Modules\Academic\Application\Exceptions\ProgramNotFound;
use Modules\Academic\Application\Responses\ProgramResponse;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\ProgramRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ProgramId;

final readonly class ReplaceProgramCoursesHandler
{
    public function __construct(
        private ProgramRepository $programs,
        private CourseRepository $courses,
    ) {}

    public function handle(ReplaceProgramCoursesCommand $command): ProgramResponse
    {
        $programId = ProgramId::fromString($command->programId);
        $program = $this->programs->findById($programId);

        if ($program === null) {
            throw ProgramNotFound::withId($command->programId);
        }

        $courseIds = array_map(
            static fn (string $courseId): CourseId => CourseId::fromString($courseId),
            $command->courseIds,
        );

        foreach ($courseIds as $courseId) {
            $course = $this->courses->findById($courseId);

            if ($course === null) {
                throw CourseNotFoundForProgram::withId($courseId->value());
            }

            if ($program->status()->isPublished() && ! $course->status()->isPublished()) {
                throw ProgramCourseNotPublished::withId($courseId->value());
            }
        }

        $program->replaceCourses($courseIds);
        $this->programs->save($program);

        return ProgramResponse::fromProgram($program);
    }
}
