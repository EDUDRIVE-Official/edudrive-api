<?php

declare(strict_types=1);

namespace Modules\AsyncProcessing\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Enums\AsyncJobStatus;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\AsyncProcessing\Infrastructure\Persistence\Eloquent\Models\AsyncJobModel;
use Modules\Foundation\Infrastructure\Persistence\DatabaseDate;

final readonly class EloquentAsyncJobRepository implements AsyncJobRepository
{
    public function save(AsyncJob $job): void
    {
        $model = AsyncJobModel::query()->updateOrCreate(
            ['id' => $job->id()->value()],
            [
                'type' => $job->type(),
                'requested_by_user_id' => $job->requestedByUserId(),
                'status' => $job->status()->value,
                'result' => $job->result(),
                'failure_reason' => $job->failureReason(),
                'started_at' => DatabaseDate::normalize($job->startedAt()),
                'completed_at' => DatabaseDate::normalize($job->completedAt()),
            ],
        );

        if ($model->wasRecentlyCreated) {
            $model->forceFill(['created_at' => DatabaseDate::normalize($job->createdAt())])->save();
        }
    }

    public function findById(AsyncJobId $id): ?AsyncJob
    {
        $model = AsyncJobModel::query()->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<AsyncJob> */
    public function allCompletedOrFailedBefore(DateTimeImmutable $threshold): array
    {
        return array_values(
            AsyncJobModel::query()
                ->whereIn('status', [AsyncJobStatus::Completed->value, AsyncJobStatus::Failed->value])
                ->where('completed_at', '<', DatabaseDate::queryValue($threshold))
                ->get()
                ->map(fn (AsyncJobModel $model): AsyncJob => $this->toDomain($model))
                ->all(),
        );
    }

    public function delete(AsyncJobId $id): void
    {
        AsyncJobModel::query()->where('id', $id->value())->delete();
    }

    private function toDomain(AsyncJobModel $model): AsyncJob
    {
        $startedAt = $model->getAttribute('started_at');
        $completedAt = $model->getAttribute('completed_at');
        $createdAt = $model->getAttribute('created_at');

        return AsyncJob::restore(
            id: AsyncJobId::fromString((string) $model->getAttribute('id')),
            type: (string) $model->getAttribute('type'),
            requestedByUserId: $model->getAttribute('requested_by_user_id') === null ? null : (string) $model->getAttribute('requested_by_user_id'),
            status: AsyncJobStatus::from((string) $model->getAttribute('status')),
            result: $model->getAttribute('result'),
            failureReason: $model->getAttribute('failure_reason') === null ? null : (string) $model->getAttribute('failure_reason'),
            createdAt: $createdAt === null ? new DateTimeImmutable('now') : DatabaseDate::restore($createdAt),
            startedAt: $startedAt === null ? null : DatabaseDate::restore($startedAt),
            completedAt: $completedAt === null ? null : DatabaseDate::restore($completedAt),
        );
    }
}
