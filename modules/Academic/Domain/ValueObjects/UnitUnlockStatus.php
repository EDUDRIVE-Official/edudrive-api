<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

final readonly class UnitUnlockStatus
{
    /** @param list<string> $lessonIds */
    public function __construct(
        public CourseUnitId $unitId,
        public bool $completed,
        public bool $unlocked,
        private array $lessonIds,
    ) {}

    public function containsLesson(LessonId $lessonId): bool
    {
        return in_array($lessonId->value(), $this->lessonIds, true);
    }
}
