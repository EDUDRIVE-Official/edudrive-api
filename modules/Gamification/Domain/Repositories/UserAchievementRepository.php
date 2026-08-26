<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Repositories;

use Modules\Gamification\Domain\Entities\UserAchievement;

interface UserAchievementRepository
{
    public function save(UserAchievement $userAchievement): void;

    public function findByAchievementAndUser(string $achievementId, string $userId): ?UserAchievement;

    /** @return list<UserAchievement> */
    public function allForUser(string $userId): array;
}
