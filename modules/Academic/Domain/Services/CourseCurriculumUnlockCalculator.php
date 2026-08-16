<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Services;

use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\ValueObjects\CurriculumUnlockStatus;
use Modules\Academic\Domain\ValueObjects\ModuleUnlockStatus;
use Modules\Academic\Domain\ValueObjects\UnitUnlockStatus;

final readonly class CourseCurriculumUnlockCalculator
{
    public function __construct(private UnitContentRepository $unitContents) {}

    public function statusFor(Course $course, EnrollmentProgress $progress): CurriculumUnlockStatus
    {
        $completedLessonIds = $progress->completedLessonIds();

        /** @var array<string, list<string>> $unitLessonIds */
        $unitLessonIds = [];
        /** @var array<string, bool> $unitCompleted */
        $unitCompleted = [];
        /** @var array<string, list<string>> $moduleUnitIds */
        $moduleUnitIds = [];

        foreach ($course->modules() as $module) {
            $unitIdsForModule = [];
            foreach ($module->units() as $unit) {
                $content = $this->unitContents->findForCourseUnit($course->id(), $unit->id());
                $lessonIds = [];
                if ($content !== null) {
                    foreach ($content->lessons() as $lesson) {
                        $lessonIds[] = $lesson->id()->value();
                    }
                }

                $unitLessonIds[$unit->id()->value()] = $lessonIds;
                $unitCompleted[$unit->id()->value()] = self::allPresent($lessonIds, $completedLessonIds);
                $unitIdsForModule[] = $unit->id()->value();
            }

            $moduleUnitIds[$module->id()->value()] = $unitIdsForModule;
        }

        /** @var array<string, bool> $moduleCompleted */
        $moduleCompleted = [];
        foreach ($moduleUnitIds as $moduleIdValue => $unitIds) {
            $moduleCompleted[$moduleIdValue] = self::allTrue($unitIds, $unitCompleted);
        }

        /** @var array<string, bool> $moduleUnlocked */
        $moduleUnlocked = [];
        foreach ($course->modules() as $module) {
            $unlocked = true;
            foreach ($module->prerequisiteModuleIds() as $prerequisiteModuleId) {
                if (! ($moduleCompleted[$prerequisiteModuleId->value()] ?? false)) {
                    $unlocked = false;
                    break;
                }
            }

            $moduleUnlocked[$module->id()->value()] = $unlocked;
        }

        $modules = [];
        foreach ($course->modules() as $module) {
            $units = [];
            foreach ($module->units() as $unit) {
                $unitPrerequisitesSatisfied = true;
                foreach ($unit->prerequisiteUnitIds() as $prerequisiteUnitId) {
                    if (! ($unitCompleted[$prerequisiteUnitId->value()] ?? false)) {
                        $unitPrerequisitesSatisfied = false;
                        break;
                    }
                }

                $units[] = new UnitUnlockStatus(
                    unitId: $unit->id(),
                    completed: $unitCompleted[$unit->id()->value()],
                    unlocked: $moduleUnlocked[$module->id()->value()] && $unitPrerequisitesSatisfied,
                    lessonIds: $unitLessonIds[$unit->id()->value()],
                );
            }

            $modules[] = new ModuleUnlockStatus(
                moduleId: $module->id(),
                completed: $moduleCompleted[$module->id()->value()],
                unlocked: $moduleUnlocked[$module->id()->value()],
                units: $units,
            );
        }

        return new CurriculumUnlockStatus($modules);
    }

    /**
     * @param  list<string>  $lessonIds
     * @param  list<string>  $completedLessonIds
     */
    private static function allPresent(array $lessonIds, array $completedLessonIds): bool
    {
        foreach ($lessonIds as $lessonId) {
            if (! in_array($lessonId, $completedLessonIds, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $ids
     * @param  array<string, bool>  $map
     */
    private static function allTrue(array $ids, array $map): bool
    {
        foreach ($ids as $id) {
            if (! ($map[$id] ?? false)) {
                return false;
            }
        }

        return true;
    }
}
