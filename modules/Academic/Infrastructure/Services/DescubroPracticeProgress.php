<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/** Durable formative practice. Never emits passport evidence, grades or rewards. */
final class DescubroPracticeProgress
{
    public const EXPERIENCE = 'crossing-accompanied';

    public const CONTENT_VERSION = '1';

    public const CRITERION_VERSION = '2.0.0-borrador.1';

    private function query(string $userId): Builder
    {
        return DB::table('academic_descubro_progress')->where('user_id', $userId)
            ->where('experience', self::EXPERIENCE)->where('content_version', self::CONTENT_VERSION);
    }

    /** @return array<string, mixed>|null */
    public function find(string $userId): ?array
    {
        $row = $this->query($userId)->first();
        if ($row === null) {
            return null;
        }
        $state = json_decode($row->state, true, 512, JSON_THROW_ON_ERROR);
        $state['revision'] = (int) $row->revision;

        return $state;
    }

    /**
     * Imports only the authenticated account's server-side legacy session.
     * Existing durable progress always wins; concurrent inserts never overwrite it.
     *
     * @return array<string, mixed>|null
     */
    public function resume(string $userId, mixed $legacy = null): ?array
    {
        $existing = $this->find($userId);
        if ($existing !== null || ! is_array($legacy)) {
            return $existing;
        }
        // A legacy browser session must not restore an administratively reset history.
        if (DB::table('identity_student_learning_resets')->where('user_id', $userId)->exists()) {
            return null;
        }
        $validator = Validator::make($legacy, [
            'phase' => ['required', 'in:learn,practice,result'],
            'lesson' => ['required', 'integer', 'between:0,2'],
            'question' => ['required', 'integer', 'between:0,3'],
            'feedback' => ['present', 'nullable', 'in:success,retry'],
            'choice' => ['present', 'nullable', 'integer', 'between:0,2'],
            'attempts' => ['present', 'array', 'max:80'],
            'attempts.*' => ['array:question,skill,choice,correct'],
            'attempts.*.question' => ['required', 'integer', 'between:0,3'],
            'attempts.*.skill' => ['required', 'in:S1,S2,S3'],
            'attempts.*.choice' => ['required', 'integer', 'between:0,2'],
            'attempts.*.correct' => ['required', 'boolean'],
            'completed_once' => ['required', 'boolean'],
            'revision' => ['required', 'integer', 'min:1'],
        ]);
        if ($validator->fails()) {
            return null;
        }
        $state = $validator->validated();
        // Ignore broken or incompatible legacy state instead of breaking the view.
        if (($state['feedback'] !== null && $state['choice'] === null)
            || ($state['choice'] === 2 && $state['question'] !== 1)
            || ($state['phase'] === 'result' && ! $state['completed_once'])) {
            return null;
        }
        $this->query($userId)->insertOrIgnore($this->row($userId, $state));

        return $this->find($userId);
    }

    /** @param array<string, mixed> $state */
    public function save(string $userId, array $state, int $expectedRevision): void
    {
        $state['revision'] = $expectedRevision + 1;
        if ($expectedRevision === 0) {
            $changed = $this->query($userId)->insertOrIgnore($this->row($userId, $state));
        } else {
            $changes = ['state' => json_encode($state, JSON_THROW_ON_ERROR), 'revision' => $state['revision'], 'updated_at' => now()];
            if ($state['completed_once']) {
                // Preserve the date of the first completed practice when repeating.
                $changes['completed_at'] = DB::raw('COALESCE(completed_at, CURRENT_TIMESTAMP)');
            }
            $changed = $this->query($userId)->where('revision', $expectedRevision)->update($changes);
        }
        abort_unless($changed === 1, 409, 'Tu avance cambió en otra pestaña o dispositivo. Recargá para continuar desde el último paso guardado.');
    }

    /** @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function row(string $userId, array $state): array
    {
        return ['id' => (string) Str::uuid(), 'user_id' => $userId, 'experience' => self::EXPERIENCE,
            'content_version' => self::CONTENT_VERSION, 'criterion_version' => self::CRITERION_VERSION,
            'revision' => (int) $state['revision'], 'state' => json_encode($state, JSON_THROW_ON_ERROR),
            'completed_at' => $state['completed_once'] ? now() : null, 'created_at' => now(), 'updated_at' => now()];
    }
}
