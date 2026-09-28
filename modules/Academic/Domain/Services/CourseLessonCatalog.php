<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Services;

use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\ValueObjects\LessonId;

final readonly class CourseLessonCatalog
{
    public function __construct(private UnitContentRepository $unitContents) {}

    /** @return list<string> */
    public function lessonIdsFor(Course $course): array
    {
        $lessonIds = [];

        foreach ($course->modules() as $module) {
            foreach ($module->units() as $unit) {
                $content = $this->unitContents->findForCourseUnit($course->id(), $unit->id());
                if ($content === null) {
                    continue;
                }

                foreach ($content->lessons() as $lesson) {
                    $lessonIds[] = $lesson->id()->value();
                }
            }
        }

        return $lessonIds;
    }

    public function lessonFor(Course $course, LessonId $lessonId): ?Lesson
    {
        foreach ($course->modules() as $module) {
            foreach ($module->units() as $unit) {
                $content = $this->unitContents->findForCourseUnit($course->id(), $unit->id());
                if ($content === null) {
                    continue;
                }

                foreach ($content->lessons() as $lesson) {
                    if ($lesson->id()->equals($lessonId)) {
                        return $lesson;
                    }
                }
            }
        }

        return null;
    }
}
