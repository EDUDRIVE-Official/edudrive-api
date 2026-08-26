<?php

declare(strict_types=1);

namespace Modules\Gamification\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Gamification\Domain\Entities\UserAchievement;
use Modules\Gamification\Domain\Repositories\UserAchievementRepository;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Models\UserAchievementModel;

final readonly class EloquentUserAchievementRepository implements UserAchievementRepository
{
    public function save(UserAchievement $userAchievement): void
    {
        UserAchievementModel::query()->updateOrCreate(
            ['id' => $userAchievement->id()],
            [
                'achievement_id' => $userAchievement->achievementId(),
                'user_id' => $userAchievement->userId(),
                'evidence' => $userAchievement->evidence(),
                'earned_at' => $userAchievement->earnedAt(),
            ],
        );
    }

    public function findByAchievementAndUser(string $achievementId, string $userId): ?UserAchievement
    {
        $model = UserAchievementModel::query()
            ->where('achievement_id', $achievementId)
            ->where('user_id', $userId)
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<UserAchievement> */
    public function allForUser(string $userId): array
    {
        return array_values(
            UserAchievementModel::query()
                ->where('user_id', $userId)
                ->orderBy('earned_at')
                ->get()
                ->map(fn (UserAchievementModel $model): UserAchievement => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(UserAchievementModel $model): UserAchievement
    {
        return UserAchievement::grant(
            id: (string) $model->getAttribute('id'),
            achievementId: (string) $model->getAttribute('achievement_id'),
            userId: (string) $model->getAttribute('user_id'),
            evidence: (string) $model->getAttribute('evidence'),
            earnedAt: new DateTimeImmutable((string) $model->getAttribute('earned_at')),
        );
    }
}
