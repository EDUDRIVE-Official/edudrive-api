<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Services;

use Illuminate\Support\Facades\DB;

final readonly class PilotReadiness
{
    public function __construct(
        private PilotVisualReviews $reviews,
        private PilotVisualReviewers $reviewers,
        private PilotVisualCandidates $candidates,
    ) {}

    /** @return array<string, mixed> */
    public function status(): array
    {
        $course = DB::table('academic_courses')->where('code', PilotDraftWorkspace::CODE)->first(['id', 'status']);
        $lessonCount = $course === null ? 0 : DB::table('academic_lessons as lesson')
            ->join('academic_course_units as unit', 'unit.id', '=', 'lesson.unit_id')
            ->join('academic_course_modules as module', 'module.id', '=', 'unit.module_id')
            ->where('module.course_id', $course->id)->count();
        $workspaceComplete = $course !== null && $course->status === 'draft' && $lessonCount === count(PilotVisualReviews::SCENES);

        $activeSpecialties = collect($this->reviewers->all())->where('active', true)->pluck('specialty')->unique()->values()->all();
        $missingSpecialties = array_values(array_diff(PilotVisualReviews::SPECIALTIES, $activeSpecialties));

        $coverage = $this->reviews->coverage();
        $reviewedScenes = collect($coverage)->filter(fn (array $scene): bool => $scene['coverage_complete'])->count();

        $bindings = $this->candidates->bindings();
        $candidateScenes = collect($bindings)->filter(fn (array $binding): bool => $binding['course_code'] === PilotDraftWorkspace::CODE && $binding['course_status'] === 'draft')->count();

        return [
            'workspace' => ['complete' => $workspaceComplete, 'lessons' => $lessonCount, 'course_id' => $course?->id],
            'reviewers' => ['complete' => $missingSpecialties === [], 'active_specialties' => $activeSpecialties, 'missing_specialties' => $missingSpecialties],
            'reviews' => ['complete' => $reviewedScenes === count(PilotVisualReviews::SCENES), 'completed_scenes' => $reviewedScenes, 'total_scenes' => count(PilotVisualReviews::SCENES)],
            'candidates' => ['complete' => $candidateScenes === count(PilotVisualReviews::SCENES), 'linked_scenes' => $candidateScenes, 'total_scenes' => count(PilotVisualReviews::SCENES)],
            'technical_preparation_complete' => $workspaceComplete && $missingSpecialties === [] && $reviewedScenes === count(PilotVisualReviews::SCENES) && $candidateScenes === count(PilotVisualReviews::SCENES),
        ];
    }
}
