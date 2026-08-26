<?php

declare(strict_types=1);

namespace Modules\Gamification\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Gamification\Domain\Entities\ExperienceEntry;
use Modules\Gamification\Domain\Repositories\ExperienceEntryRepository;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Models\ExperienceEntryModel;

final readonly class EloquentExperienceEntryRepository implements ExperienceEntryRepository
{
    public function save(ExperienceEntry $entry): void
    {
        ExperienceEntryModel::query()->updateOrCreate(
            ['id' => $entry->id()],
            [
                'user_id' => $entry->userId(),
                'points' => $entry->points(),
                'competency_id' => $entry->competencyId(),
                'reason' => $entry->reason(),
                'recorded_at' => $entry->recordedAt(),
            ],
        );
    }

    /** @return list<ExperienceEntry> */
    public function allForUser(string $userId): array
    {
        return array_values(
            ExperienceEntryModel::query()
                ->where('user_id', $userId)
                ->orderBy('recorded_at')
                ->get()
                ->map(fn (ExperienceEntryModel $model): ExperienceEntry => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(ExperienceEntryModel $model): ExperienceEntry
    {
        $competencyId = $model->getAttribute('competency_id');

        return ExperienceEntry::record(
            id: (string) $model->getAttribute('id'),
            userId: (string) $model->getAttribute('user_id'),
            points: (int) $model->getAttribute('points'),
            competencyId: $competencyId === null ? null : (string) $competencyId,
            reason: (string) $model->getAttribute('reason'),
            recordedAt: new DateTimeImmutable((string) $model->getAttribute('recorded_at')),
        );
    }
}
