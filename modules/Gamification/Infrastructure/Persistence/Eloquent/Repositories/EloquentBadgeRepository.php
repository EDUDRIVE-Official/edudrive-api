<?php

declare(strict_types=1);

namespace Modules\Gamification\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Gamification\Domain\Aggregates\Badge;
use Modules\Gamification\Domain\Enums\BadgeCategory;
use Modules\Gamification\Domain\Enums\BadgeLevel;
use Modules\Gamification\Domain\Enums\BadgeStatus;
use Modules\Gamification\Domain\Repositories\BadgeRepository;
use Modules\Gamification\Domain\ValueObjects\BadgeCode;
use Modules\Gamification\Domain\ValueObjects\BadgeId;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Models\BadgeModel;

final readonly class EloquentBadgeRepository implements BadgeRepository
{
    public function save(Badge $badge): void
    {
        BadgeModel::query()->updateOrCreate(
            ['id' => $badge->id()->value()],
            [
                'code' => $badge->code()->value(),
                'name' => $badge->name(),
                'description' => $badge->description(),
                'criteria' => $badge->criteria(),
                'category' => $badge->category()->value,
                'level' => $badge->level()->value,
                'version' => $badge->version(),
                'status' => $badge->status()->value,
                'registered_at' => $badge->registeredAt(),
                'retired_at' => $badge->retiredAt(),
                'retired_reason' => $badge->retiredReason(),
            ],
        );
    }

    public function findById(BadgeId $id): ?Badge
    {
        $model = BadgeModel::query()->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByCode(BadgeCode $code): ?Badge
    {
        $model = BadgeModel::query()->where('code', $code->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<Badge> */
    public function all(): array
    {
        return array_values(
            BadgeModel::query()
                ->orderBy('registered_at')
                ->get()
                ->map(fn (BadgeModel $model): Badge => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(BadgeModel $model): Badge
    {
        $retiredAt = $model->getAttribute('retired_at');
        $retiredReason = $model->getAttribute('retired_reason');

        return Badge::restore(
            id: BadgeId::fromString((string) $model->getAttribute('id')),
            code: BadgeCode::fromString((string) $model->getAttribute('code')),
            name: (string) $model->getAttribute('name'),
            description: (string) $model->getAttribute('description'),
            criteria: (string) $model->getAttribute('criteria'),
            category: BadgeCategory::from((string) $model->getAttribute('category')),
            level: BadgeLevel::from((string) $model->getAttribute('level')),
            version: (int) $model->getAttribute('version'),
            status: BadgeStatus::from((string) $model->getAttribute('status')),
            registeredAt: new DateTimeImmutable((string) $model->getAttribute('registered_at')),
            retiredAt: $retiredAt === null ? null : new DateTimeImmutable((string) $retiredAt),
            retiredReason: $retiredReason === null ? null : (string) $retiredReason,
        );
    }
}
