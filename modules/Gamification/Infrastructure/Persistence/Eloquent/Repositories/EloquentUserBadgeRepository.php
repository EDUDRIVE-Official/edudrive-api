<?php

declare(strict_types=1);

namespace Modules\Gamification\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Gamification\Domain\Entities\UserBadge;
use Modules\Gamification\Domain\Repositories\UserBadgeRepository;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Models\UserBadgeModel;

final readonly class EloquentUserBadgeRepository implements UserBadgeRepository
{
    public function save(UserBadge $userBadge): void
    {
        UserBadgeModel::query()->updateOrCreate(
            ['id' => $userBadge->id()],
            [
                'badge_id' => $userBadge->badgeId(),
                'user_id' => $userBadge->userId(),
                'awarded_version' => $userBadge->awardedVersion(),
                'evidence' => $userBadge->evidence(),
                'earned_at' => $userBadge->earnedAt(),
            ],
        );
    }

    public function findByBadgeAndUser(string $badgeId, string $userId): ?UserBadge
    {
        $model = UserBadgeModel::query()
            ->where('badge_id', $badgeId)
            ->where('user_id', $userId)
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<UserBadge> */
    public function allForUser(string $userId): array
    {
        return array_values(
            UserBadgeModel::query()
                ->where('user_id', $userId)
                ->orderBy('earned_at')
                ->get()
                ->map(fn (UserBadgeModel $model): UserBadge => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(UserBadgeModel $model): UserBadge
    {
        return UserBadge::grant(
            id: (string) $model->getAttribute('id'),
            badgeId: (string) $model->getAttribute('badge_id'),
            userId: (string) $model->getAttribute('user_id'),
            awardedVersion: (int) $model->getAttribute('awarded_version'),
            evidence: (string) $model->getAttribute('evidence'),
            earnedAt: new DateTimeImmutable((string) $model->getAttribute('earned_at')),
        );
    }
}
