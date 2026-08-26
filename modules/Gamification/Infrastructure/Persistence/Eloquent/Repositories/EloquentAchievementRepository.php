<?php

declare(strict_types=1);

namespace Modules\Gamification\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Gamification\Domain\Aggregates\Achievement;
use Modules\Gamification\Domain\Enums\AchievementStatus;
use Modules\Gamification\Domain\Repositories\AchievementRepository;
use Modules\Gamification\Domain\ValueObjects\AchievementCode;
use Modules\Gamification\Domain\ValueObjects\AchievementId;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Models\AchievementModel;

final readonly class EloquentAchievementRepository implements AchievementRepository
{
    public function save(Achievement $achievement): void
    {
        AchievementModel::query()->updateOrCreate(
            ['id' => $achievement->id()->value()],
            [
                'code' => $achievement->code()->value(),
                'name' => $achievement->name(),
                'description' => $achievement->description(),
                'earning_rule' => $achievement->earningRule(),
                'status' => $achievement->status()->value,
                'registered_at' => $achievement->registeredAt(),
                'retired_at' => $achievement->retiredAt(),
                'retired_reason' => $achievement->retiredReason(),
            ],
        );
    }

    public function findById(AchievementId $id): ?Achievement
    {
        $model = AchievementModel::query()->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByCode(AchievementCode $code): ?Achievement
    {
        $model = AchievementModel::query()->where('code', $code->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<Achievement> */
    public function all(): array
    {
        return array_values(
            AchievementModel::query()
                ->orderBy('registered_at')
                ->get()
                ->map(fn (AchievementModel $model): Achievement => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(AchievementModel $model): Achievement
    {
        $retiredAt = $model->getAttribute('retired_at');
        $retiredReason = $model->getAttribute('retired_reason');

        return Achievement::restore(
            id: AchievementId::fromString((string) $model->getAttribute('id')),
            code: AchievementCode::fromString((string) $model->getAttribute('code')),
            name: (string) $model->getAttribute('name'),
            description: (string) $model->getAttribute('description'),
            earningRule: (string) $model->getAttribute('earning_rule'),
            status: AchievementStatus::from((string) $model->getAttribute('status')),
            registeredAt: new DateTimeImmutable((string) $model->getAttribute('registered_at')),
            retiredAt: $retiredAt === null ? null : new DateTimeImmutable((string) $retiredAt),
            retiredReason: $retiredReason === null ? null : (string) $retiredReason,
        );
    }
}
