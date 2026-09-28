<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Internal review records only; never an educational or regulatory approval. */
final class PilotVisualReviews
{
    public const VERSION = 'p912-visual-v0.1';

    public const SCENES = ['van', 'turn', 'barrier', 'descent'];

    public const STATUSES = ['changes_required', 'ready_for_specialist_review'];

    public const CRITERIA = ['road_fidelity', 'decision_clarity', 'responsible_outcome', 'equivalent_access'];

    public const SPECIALTIES = ['education', 'road_safety', 'accessibility'];

    /** @return array<string, array<string, mixed>> */
    public function forReviewer(string $reviewer): array
    {
        return DB::table('academic_pilot_visual_reviews')
            ->where('reviewer_user_id', $reviewer)
            ->where('scene_version', self::VERSION)
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->scene => [
                'specialty' => $row->specialty,
                'reviewed_on' => $row->reviewed_on,
                'criteria' => json_decode($row->criteria, true, 512, JSON_THROW_ON_ERROR),
                'findings' => $row->findings ?? '',
                'status' => $row->status,
                'updated_at' => $row->updated_at,
            ]])->all();
    }

    /** @param array<string, mixed> $data */
    public function save(string $reviewer, array $data, string $designationEventId): void
    {
        abort_unless(in_array($data['scene'], self::SCENES, true), 422);
        abort_unless(in_array($data['status'], self::STATUSES, true), 422);
        abort_unless(in_array($data['specialty'], self::SPECIALTIES, true), 422);
        abort_unless(DB::table('academic_pilot_visual_reviewer_events')->where('id', $designationEventId)->where('user_id', $reviewer)->exists(), 422);
        $criteria = [];
        foreach (self::CRITERIA as $criterion) {
            $criteria[$criterion] = (bool) ($data['criteria'][$criterion] ?? false);
        }
        if ($data['status'] === 'ready_for_specialist_review') {
            abort_unless(! in_array(false, $criteria, true), 422, 'Confirmá todos los criterios antes de enviar a revisión especializada.');
        }
        if ($data['status'] === 'changes_required') {
            abort_unless(trim((string) ($data['findings'] ?? '')) !== '', 422, 'Describí el cambio necesario para que el hallazgo sea accionable.');
        }
        $now = now();
        DB::table('academic_pilot_visual_reviews')->upsert([[
            'id' => (string) Str::uuid(),
            'reviewer_user_id' => $reviewer,
            'reviewer_designation_event_id' => $designationEventId,
            'scene' => $data['scene'],
            'scene_version' => self::VERSION,
            'specialty' => $data['specialty'],
            'reviewed_on' => $data['reviewed_on'],
            'criteria' => json_encode($criteria, JSON_THROW_ON_ERROR),
            'findings' => trim($data['findings'] ?? '') ?: null,
            'status' => $data['status'],
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['reviewer_user_id', 'scene', 'scene_version'], ['reviewer_designation_event_id', 'specialty', 'reviewed_on', 'criteria', 'findings', 'status', 'updated_at']);
    }

    /** @return array<string, array<string, mixed>> */
    public function coverage(): array
    {
        $rows = DB::table('academic_pilot_visual_reviews')->where('scene_version', self::VERSION)->get();
        $coverage = [];
        foreach (self::SCENES as $scene) {
            $sceneRows = $rows->where('scene', $scene);
            $readySpecialties = $sceneRows->where('status', 'ready_for_specialist_review')->pluck('specialty')->unique()->values()->all();
            $missing = array_values(array_diff(self::SPECIALTIES, $readySpecialties));
            $hasChanges = $sceneRows->contains(fn ($row): bool => $row->status === 'changes_required');
            $blockers = $sceneRows
                ->where('status', 'changes_required')
                ->map(fn ($row): array => [
                    'specialty' => $row->specialty,
                    'reviewed_on' => $row->reviewed_on,
                    'finding' => $row->findings,
                ])
                ->values()
                ->all();
            $coverage[$scene] = [
                'reviews' => $sceneRows->count(),
                'ready_specialties' => $readySpecialties,
                'missing_specialties' => $missing,
                'has_changes' => $hasChanges,
                'blockers' => $blockers,
                'coverage_complete' => ! $hasChanges && $missing === [],
            ];
        }

        return $coverage;
    }
}
