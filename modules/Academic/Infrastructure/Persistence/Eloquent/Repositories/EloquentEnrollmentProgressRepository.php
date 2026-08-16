<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\Entities\LessonCompletion;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\LessonId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\EnrollmentLessonCompletionModel;

final readonly class EloquentEnrollmentProgressRepository implements EnrollmentProgressRepository
{
    public function save(EnrollmentProgress $progress): void
    {
        $rows = array_map(static fn (LessonCompletion $completion): array => [
            'id' => (string) Str::uuid(),
            'enrollment_id' => $progress->enrollmentId()->value(),
            'lesson_id' => $completion->lessonId()->value(),
            'completed_at' => $completion->completedAt(),
            'time_spent_minutes' => $completion->timeSpentMinutes(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $progress->lessonCompletions());

        if ($rows === []) {
            return;
        }

        EnrollmentLessonCompletionModel::query()->upsert(
            $rows,
            ['enrollment_id', 'lesson_id'],
            ['completed_at', 'time_spent_minutes', 'updated_at'],
        );
    }

    public function findByEnrollmentId(EnrollmentId $enrollmentId): EnrollmentProgress
    {
        $completions = EnrollmentLessonCompletionModel::query()
            ->where('enrollment_id', $enrollmentId->value())
            ->orderBy('completed_at')
            ->get()
            ->map(fn (EnrollmentLessonCompletionModel $model): LessonCompletion => LessonCompletion::create(
                LessonId::fromString((string) $model->getAttribute('lesson_id')),
                $model->getAttribute('completed_at'),
                $model->getAttribute('time_spent_minutes') === null ? null : (int) $model->getAttribute('time_spent_minutes'),
            ))
            ->all();

        return EnrollmentProgress::restore($enrollmentId, array_values($completions));
    }
}
