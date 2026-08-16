<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

final readonly class CurriculumUnlockStatus
{
    /** @param list<ModuleUnlockStatus> $modules */
    public function __construct(public array $modules) {}

    public function isUnitUnlocked(CourseUnitId $unitId): bool
    {
        foreach ($this->modules as $module) {
            foreach ($module->units as $unit) {
                if ($unit->unitId->equals($unitId)) {
                    return $unit->unlocked;
                }
            }
        }

        return false;
    }

    public function unitIdForLesson(LessonId $lessonId): ?CourseUnitId
    {
        foreach ($this->modules as $module) {
            foreach ($module->units as $unit) {
                if ($unit->containsLesson($lessonId)) {
                    return $unit->unitId;
                }
            }
        }

        return null;
    }
}
