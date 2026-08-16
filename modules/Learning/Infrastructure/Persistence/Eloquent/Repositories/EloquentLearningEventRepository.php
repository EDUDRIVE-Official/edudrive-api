<?php

declare(strict_types=1);

namespace Modules\Learning\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Learning\Domain\Entities\LearningEvent;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Learning\Domain\ValueObjects\LearningEventId;
use Modules\Learning\Domain\ValueObjects\LearningVerb;
use Modules\Learning\Infrastructure\Persistence\Eloquent\Models\LearningEventModel;

final class EloquentLearningEventRepository implements LearningEventRepository
{
    public function record(LearningEvent $event): void
    {
        LearningEventModel::query()->create([
            'id' => $event->id()->value(),
            'enrollment_id' => $event->enrollmentId(),
            'user_id' => $event->userId(),
            'course_id' => $event->courseId(),
            'verb' => $event->verb()->value,
            'subject_id' => $event->subjectId(),
            'evidence' => $event->evidence(),
            'occurred_at' => $event->occurredAt(),
        ]);
    }

    /** @return list<LearningEvent> */
    public function findByEnrollmentId(string $enrollmentId): array
    {
        // array_values() ensures a list<> return type for PHPStan
        return array_values(
            LearningEventModel::query()
                ->where('enrollment_id', $enrollmentId)
                ->orderByDesc('occurred_at')
                ->get()
                ->map(fn (LearningEventModel $model): LearningEvent => LearningEvent::create(
                    id: LearningEventId::fromString($model->id),
                    enrollmentId: $model->enrollment_id,
                    userId: $model->user_id,
                    courseId: $model->course_id,
                    verb: LearningVerb::from($model->verb),
                    subjectId: $model->subject_id,
                    occurredAt: DateTimeImmutable::createFromInterface($model->occurred_at),
                    evidence: $model->evidence,
                ))
                ->all(),
        );
    }
}
