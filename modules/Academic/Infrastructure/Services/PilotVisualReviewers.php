<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Enums\Role;

/** Internal designations only; they do not certify professional credentials. */
final class PilotVisualReviewers
{
    public function __construct(private readonly PermissionChecker $permissions) {}

    /** @return array<string, mixed>|null */
    public function activeForUser(string $userId): ?array
    {
        $row = DB::table('academic_pilot_visual_reviewers')
            ->where('user_id', $userId)
            ->where('active', true)
            ->first();

        if ($row === null) {
            return null;
        }
        $designation = (array) $row;
        $designation['event_id'] = $row->current_event_id;

        return $designation;
    }

    /** @return array<int, array<array-key, mixed>> */
    public function all(): array
    {
        return DB::table('academic_pilot_visual_reviewers as reviewer')
            ->join('users as user', 'user.id', '=', 'reviewer.user_id')
            ->orderByDesc('reviewer.active')->orderBy('user.name')
            ->get(['reviewer.*', 'user.name', 'user.email'])
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    /** @return array<int, array<array-key, mixed>> */
    public function history(): array
    {
        return DB::table('academic_pilot_visual_reviewer_events as event')
            ->join('users as user', 'user.id', '=', 'event.user_id')
            ->orderByDesc('event.created_at')->orderByDesc('event.id')
            ->get(['event.*', 'user.name', 'user.email'])
            ->map(fn ($row): array => (array) $row)->all();
    }

    /** @return array<int, array<array-key, mixed>> */
    public function coordination(): array
    {
        $reviews = DB::table('academic_pilot_visual_reviews')
            ->where('scene_version', PilotVisualReviews::VERSION)
            ->get()
            ->groupBy('reviewer_user_id');

        return collect($this->all())
            ->where('active', true)
            ->map(function (array $reviewer) use ($reviews): array {
                $records = $reviews->get($reviewer['user_id'], collect());
                $byScene = [];
                foreach (PilotVisualReviews::SCENES as $scene) {
                    $record = $records->firstWhere('scene', $scene);
                    $byScene[$scene] = $record === null ? 'pending' : $record->status;
                }

                $reviewer['progress'] = [
                    'completed' => $records->count(),
                    'total' => count(PilotVisualReviews::SCENES),
                    'ready' => $records->where('status', 'ready_for_specialist_review')->count(),
                    'changes' => $records->where('status', 'changes_required')->count(),
                    'scenes' => $byScene,
                ];

                return $reviewer;
            })
            ->values()
            ->all();
    }

    /** @return array<int, array<array-key, mixed>> */
    public function eligibleUsers(): array
    {
        $teacherIds = DB::table('authorization_role_assignments')
            ->where('role', Role::Teacher->value)
            ->pluck('user_id')
            ->all();

        return DB::table('users')->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email'])
            ->filter(fn ($row): bool => in_array($row->id, $teacherIds, true)
                || $this->permissions->userHasPermission($row->id, Permission::ManageCourses))
            ->map(fn ($row): array => (array) $row)->values()->all();
    }

    /** @param array<string, mixed> $data */
    public function save(array $data, string $designatedBy): void
    {
        abort_unless(in_array($data['specialty'], PilotVisualReviews::SPECIALTIES, true), 422);
        abort_unless(DB::table('users')->where('id', $data['user_id'])->where('status', 'active')->exists(), 422, 'La cuenta revisora debe estar activa.');
        $isTeacher = DB::table('authorization_role_assignments')
            ->where('user_id', $data['user_id'])
            ->where('role', Role::Teacher->value)
            ->exists();
        abort_unless($isTeacher || $this->permissions->userHasPermission($data['user_id'], Permission::ManageCourses), 422, 'La cuenta revisora debe tener rol docente o acceso al flujo interno de cursos.');
        DB::transaction(function () use ($data, $designatedBy): void {
            $now = now();
            $eventId = (string) Str::uuid();
            $snapshot = [
                'id' => $eventId,
                'user_id' => $data['user_id'],
                'specialty' => $data['specialty'],
                'organization' => trim($data['organization']),
                'qualification' => trim($data['qualification']),
                'evidence_reference' => trim($data['evidence_reference']),
                'designated_by' => $designatedBy,
                'designated_on' => $data['designated_on'],
                'active' => (bool) $data['active'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
            DB::table('academic_pilot_visual_reviewer_events')->insert($snapshot);
            $current = $snapshot;
            $current['id'] = (string) Str::uuid();
            $current['current_event_id'] = $eventId;
            DB::table('academic_pilot_visual_reviewers')->upsert([$current], ['user_id'], ['current_event_id', 'specialty', 'organization', 'qualification', 'evidence_reference', 'designated_by', 'designated_on', 'active', 'updated_at']);
        });
    }
}
