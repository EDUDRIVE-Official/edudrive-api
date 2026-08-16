<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Services;

use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\UnitContentRepository;

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
}
