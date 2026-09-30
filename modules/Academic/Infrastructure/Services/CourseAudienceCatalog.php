<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Services;

use Illuminate\Support\Facades\DB;

/** Reads existing classifications; never infers an audience from a course title. */
final class CourseAudienceCatalog
{
    public const LABELS = [
        'explore' => '5–8 años', 'discover' => '9–12 años',
        'understand' => '13–15 años', 'prepare' => '16–17 años',
        'drive' => '18–24 años', 'perfect' => '25–59 años',
        'refresh' => '60+ años', 'teach' => 'Docentes y acompañantes',
    ];

    /** @param list<string> $courseIds
     * @return array<string, array{label: string, stage: ?string, lessons: int, classified: int}>
     */
    public function forCourses(array $courseIds): array
    {
        $rows = DB::table('academic_courses as course')
            ->leftJoin('academic_course_modules as module', 'module.course_id', '=', 'course.id')
            ->leftJoin('academic_course_units as unit', 'unit.module_id', '=', 'module.id')
            ->leftJoin('academic_lessons as lesson', 'lesson.unit_id', '=', 'unit.id')
            ->whereIn('course.id', $courseIds)
            ->get(['course.id as course_id', 'lesson.id as lesson_id', 'lesson.learning_design']);

        $result = [];
        foreach ($rows->groupBy('course_id') as $courseId => $lessons) {
            $stages = [];
            $count = 0;
            $classified = 0;
            $incomplete = false;
            foreach ($lessons as $lesson) {
                if ($lesson->lesson_id === null) {
                    $incomplete = true;

                    continue;
                }
                $count++;
                $design = json_decode($lesson->learning_design ?? 'null', true);
                $stage = is_array($design) ? ($design['stage'] ?? null) : null;
                if (! is_string($stage) || ! isset(self::LABELS[$stage])) {
                    $incomplete = true;

                    continue;
                }
                $classified++;
                $stages[$stage] = true;
            }
            // A mixed-stage course requires an explicit route design before recommending it.
            $stage = ! $incomplete && $count > 0 && count($stages) === 1 ? array_key_first($stages) : null;
            $result[$courseId] = [
                'stage' => $stage,
                'label' => $stage !== null ? self::LABELS[$stage] : ($incomplete || $count === 0 ? 'Público pendiente de revisión' : 'Varias etapas: requiere orientación'),
                'lessons' => $count,
                'classified' => $classified,
            ];
        }

        return $result;
    }
}
