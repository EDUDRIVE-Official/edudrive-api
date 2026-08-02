<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\CreateCourseCommand;
use Modules\Academic\Application\Exceptions\CourseCodeAlreadyExists;
use Modules\Academic\Application\Responses\CreateCourseResponse;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Enums\CourseModality;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;

final readonly class CreateCourseHandler
{
    public function __construct(
        private CourseRepository $courses,
    ) {}

    public function handle(
        CreateCourseCommand $command,
    ): CreateCourseResponse {
        $code = CourseCode::fromString($command->code);

        if ($this->courses->existsByCode($code)) {
            throw CourseCodeAlreadyExists::forCode($code);
        }

        $course = Course::create(
            id: CourseId::fromString((string) Str::uuid()),
            code: $code,
            title: CourseTitle::fromString($command->title),
            description: $command->description,
            objectives: $command->objectives,
            prerequisites: $command->prerequisites,
            modality: $command->modality === null ? null : CourseModality::from($command->modality),
            durationHours: $command->durationHours,
        );

        $this->courses->save($course);

        return CreateCourseResponse::fromCourse($course);
    }
}
