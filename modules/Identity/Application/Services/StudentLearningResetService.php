<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\StudentLearningResetModel;

final class StudentLearningResetService
{
    /** @return array{reset: StudentLearningResetModel, summary: array<string, int>} */
    public function reset(string $userId, string $actorId, string $reason): array
    {
        return DB::transaction(function () use ($userId, $actorId, $reason): array {
            $snapshot = [];

            $enrollmentIds = $this->ids('academic_enrollments', 'user_id', $userId);
            $attemptIds = $this->ids('academic_exam_attempts', 'user_id', $userId);
            $passportIds = $this->ids('road_passports', 'user_id', $userId);
            $certificateIds = $this->ids('certificates', 'user_id', $userId);
            $sessionIds = $this->ids('simulation_sessions', 'user_id', $userId);

            $this->captureWhere($snapshot, 'academic_enrollment_lesson_completions', 'enrollment_id', $enrollmentIds);
            $this->captureWhere($snapshot, 'academic_exam_attempt_questions', 'attempt_id', $attemptIds);
            $this->captureWhere($snapshot, 'road_passport_history_entries', 'road_passport_id', $passportIds);
            $this->captureWhere($snapshot, 'road_passport_evidence', 'road_passport_id', $passportIds);
            $this->captureWhere($snapshot, 'certificate_history_entries', 'certificate_id', $certificateIds);
            foreach (['simulation_session_history_entries', 'telemetry_samples', 'telemetry_events', 'decision_points'] as $table) {
                $this->captureWhere($snapshot, $table, 'simulation_session_id', $sessionIds);
            }
            foreach (['academic_descubro_progress', 'academic_enrollments', 'academic_exam_attempts', 'learning_events', 'road_passports', 'certificates', 'user_achievements', 'user_badges', 'experience_entries', 'challenge_participations', 'simulation_sessions'] as $table) {
                $this->captureWhere($snapshot, $table, 'user_id', [$userId]);
            }

            $summary = array_map('count', $snapshot);
            $now = now();
            $reset = StudentLearningResetModel::query()->create([
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'performed_by_user_id' => $actorId,
                'reason' => trim($reason),
                'summary' => $summary,
                'encrypted_snapshot' => ['version' => 1, 'captured_at' => $now->toIso8601String(), 'records' => $snapshot],
                'reset_at' => $now,
            ]);

            foreach (['academic_descubro_progress', 'academic_enrollments', 'academic_exam_attempts', 'learning_events', 'certificates', 'user_achievements', 'user_badges', 'experience_entries', 'challenge_participations', 'simulation_sessions', 'road_passports'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->where('user_id', $userId)->delete();
                }
            }

            if (Schema::hasTable('road_passports')) {
                DB::table('road_passports')->insert([
                    'id' => (string) Str::uuid(), 'user_id' => $userId, 'status' => 'active', 'level' => 1,
                    'issued_at' => $now, 'verification_version' => 1, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            return ['reset' => $reset, 'summary' => $summary];
        });
    }

    /** @return list<string> */
    private function ids(string $table, string $column, string $value): array
    {
        return Schema::hasTable($table) ? array_values(DB::table($table)->where($column, $value)->pluck('id')->map(static fn ($id): string => (string) $id)->all()) : [];
    }

    /**
     * @param  array<string, list<array<array-key, mixed>>>  $snapshot
     * @param  list<string>  $values
     */
    private function captureWhere(array &$snapshot, string $table, string $column, array $values): void
    {
        if (! Schema::hasTable($table) || $values === []) {
            $snapshot[$table] = [];

            return;
        }

        $snapshot[$table] = array_values(DB::table($table)->whereIn($column, $values)->get()->map(static fn (object $row): array => (array) $row)->all());
    }
}
