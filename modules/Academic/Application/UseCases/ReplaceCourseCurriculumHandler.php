<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\ReplaceCourseCurriculumCommand;
use Modules\Academic\Application\DTO\CourseModuleInput;
use Modules\Academic\Application\DTO\CourseUnitInput;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Responses\CourseCurriculumResponse;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseModuleId;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;

final readonly class ReplaceCourseCurriculumHandler
{
    public function __construct(
        private CourseRepository $courses,
    ) {}

    public function handle(ReplaceCourseCurriculumCommand $command): CourseCurriculumResponse
    {
        $courseId = CourseId::fromString($command->courseId);
        $course = $this->courses->findById($courseId);

        if ($course === null) {
            throw CourseNotFound::withId($command->courseId);
        }

        $modules = array_map(
            static fn (CourseModuleInput $module): CourseModule => CourseModule::create(
                id: CourseModuleId::fromString($module->id),
                code: CurriculumCode::fromString($module->code),
                title: $module->title,
                description: $module->description,
                objectives: $module->objectives,
                durationMinutes: $module->durationMinutes,
                position: $module->position,
                prerequisiteModuleIds: array_map(
                    static fn (string $id): CourseModuleId => CourseModuleId::fromString($id),
                    $module->prerequisiteModuleIds,
                ),
                units: array_map(
                    static fn (CourseUnitInput $unit): CourseUnit => CourseUnit::create(
                        id: CourseUnitId::fromString($unit->id),
                        code: CurriculumCode::fromString($unit->code),
                        title: $unit->title,
                        description: $unit->description,
                        objectives: $unit->objectives,
                        durationMinutes: $unit->durationMinutes,
                        position: $unit->position,
                        prerequisiteUnitIds: array_map(
                            static fn (string $id): CourseUnitId => CourseUnitId::fromString($id),
                            $unit->prerequisiteUnitIds,
                        ),
                    ),
                    $module->units,
                ),
            ),
            $command->modules,
        );

        $course->replaceCurriculum($modules);
        $this->courses->save($course);

        return CourseCurriculumResponse::fromCourse($course);
    }
}
