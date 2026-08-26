<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Repositories;

use Modules\Gamification\Domain\Entities\UserBadge;

interface UserBadgeRepository
{
    public function save(UserBadge $userBadge): void;

    public function findByBadgeAndUser(string $badgeId, string $userId): ?UserBadge;

    /** @return list<UserBadge> */
    public function allForUser(string $userId): array;
}
