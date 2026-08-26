<?php

declare(strict_types=1);

namespace Modules\Gamification\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Gamification\Domain\Aggregates\Challenge;
use Modules\Gamification\Domain\Enums\ChallengeStatus;
use Modules\Gamification\Domain\Enums\ChallengeType;
use Modules\Gamification\Domain\Repositories\ChallengeRepository;
use Modules\Gamification\Domain\ValueObjects\ChallengeCode;
use Modules\Gamification\Domain\ValueObjects\ChallengeId;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Models\ChallengeModel;

final readonly class EloquentChallengeRepository implements ChallengeRepository
{
    public function save(Challenge $challenge): void
    {
        ChallengeModel::query()->updateOrCreate(
            ['id' => $challenge->id()->value()],
            [
                'code' => $challenge->code()->value(),
                'name' => $challenge->name(),
                'description' => $challenge->description(),
                'type' => $challenge->type()->value,
                'reward' => $challenge->reward(),
                'starts_at' => $challenge->startsAt(),
                'ends_at' => $challenge->endsAt(),
                'status' => $challenge->status()->value,
                'registered_at' => $challenge->registeredAt(),
                'retired_at' => $challenge->retiredAt(),
                'retired_reason' => $challenge->retiredReason(),
            ],
        );
    }

    public function findById(ChallengeId $id): ?Challenge
    {
        $model = ChallengeModel::query()->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByCode(ChallengeCode $code): ?Challenge
    {
        $model = ChallengeModel::query()->where('code', $code->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<Challenge> */
    public function all(): array
    {
        return array_values(
            ChallengeModel::query()
                ->orderBy('registered_at')
                ->get()
                ->map(fn (ChallengeModel $model): Challenge => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(ChallengeModel $model): Challenge
    {
        $retiredAt = $model->getAttribute('retired_at');
        $retiredReason = $model->getAttribute('retired_reason');

        return Challenge::restore(
            id: ChallengeId::fromString((string) $model->getAttribute('id')),
            code: ChallengeCode::fromString((string) $model->getAttribute('code')),
            name: (string) $model->getAttribute('name'),
            description: (string) $model->getAttribute('description'),
            type: ChallengeType::from((string) $model->getAttribute('type')),
            reward: (string) $model->getAttribute('reward'),
            startsAt: new DateTimeImmutable((string) $model->getAttribute('starts_at')),
            endsAt: new DateTimeImmutable((string) $model->getAttribute('ends_at')),
            status: ChallengeStatus::from((string) $model->getAttribute('status')),
            registeredAt: new DateTimeImmutable((string) $model->getAttribute('registered_at')),
            retiredAt: $retiredAt === null ? null : new DateTimeImmutable((string) $retiredAt),
            retiredReason: $retiredReason === null ? null : (string) $retiredReason,
        );
    }
}
