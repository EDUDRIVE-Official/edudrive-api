<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Enums\CourseStatus;

/** Candidate links only; this service never modifies lesson content or course state. */
final readonly class PilotVisualCandidates
{
    public function __construct(private PilotVisualReviews $reviews) {}

    /** @return list<array<string, string>> */
    public function draftLessons(): array
    {
        return DB::table('academic_lessons as lesson')
            ->join('academic_course_units as unit', 'unit.id', '=', 'lesson.unit_id')
            ->join('academic_course_modules as module', 'module.id', '=', 'unit.module_id')
            ->join('academic_courses as course', 'course.id', '=', 'module.course_id')
            ->where('course.status', CourseStatus::Draft->value)
            ->orderBy('course.title')->orderBy('module.position')->orderBy('unit.position')->orderBy('lesson.position')
            ->get(['course.id as course_id', 'course.title as course_title', 'module.title as module_title', 'unit.title as unit_title', 'lesson.id as lesson_id', 'lesson.title as lesson_title'])
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    /** @return array<string, array<string, string>> */
    public function bindings(): array
    {
        return DB::table('academic_pilot_visual_candidates as candidate')
            ->join('academic_lessons as lesson', 'lesson.id', '=', 'candidate.lesson_id')
            ->join('academic_course_units as unit', 'unit.id', '=', 'lesson.unit_id')
            ->join('academic_course_modules as module', 'module.id', '=', 'unit.module_id')
            ->join('academic_courses as course', 'course.id', '=', 'candidate.course_id')
            ->where('candidate.scene_version', PilotVisualReviews::VERSION)
            ->get(['candidate.scene', 'candidate.linked_at', 'course.id as course_id', 'course.code as course_code', 'course.title as course_title', 'course.status as course_status', 'module.title as module_title', 'unit.title as unit_title', 'lesson.id as lesson_id', 'lesson.title as lesson_title'])
            ->mapWithKeys(fn ($row): array => [$row->scene => (array) $row])
            ->all();
    }

    public function assign(string $scene, string $lessonId, string $linkedBy): void
    {
        abort_unless(in_array($scene, PilotVisualReviews::SCENES, true), 422);
        $coverage = $this->reviews->coverage();
        abort_unless($coverage[$scene]['coverage_complete'], 422, 'La escena todavía no tiene cobertura completa de revisión.');

        $target = DB::table('academic_lessons as lesson')
            ->join('academic_course_units as unit', 'unit.id', '=', 'lesson.unit_id')
            ->join('academic_course_modules as module', 'module.id', '=', 'unit.module_id')
            ->join('academic_courses as course', 'course.id', '=', 'module.course_id')
            ->where('lesson.id', $lessonId)
            ->where('course.status', CourseStatus::Draft->value)
            ->first(['course.id as course_id']);
        abort_unless($target !== null, 422, 'La lección debe pertenecer a un curso en borrador.');

        $now = now();
        DB::table('academic_pilot_visual_candidates')->upsert([[
            'id' => (string) Str::uuid(),
            'scene' => $scene,
            'scene_version' => PilotVisualReviews::VERSION,
            'course_id' => $target->course_id,
            'lesson_id' => $lessonId,
            'linked_by' => $linkedBy,
            'linked_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['scene', 'scene_version'], ['course_id', 'lesson_id', 'linked_by', 'linked_at', 'updated_at']);
    }
}
