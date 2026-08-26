<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use Modules\Gamification\Application\Queries\GetMyAchievementsQuery;
use Modules\Gamification\Application\Responses\UserAchievementResponse;
use Modules\Gamification\Domain\Entities\UserAchievement;
use Modules\Gamification\Domain\Repositories\UserAchievementRepository;

final readonly class GetMyAchievementsHandler
{
    public function __construct(private UserAchievementRepository $userAchievements) {}

    /** @return list<UserAchievementResponse> */
    public function handle(GetMyAchievementsQuery $query): array
    {
        return array_map(
            static fn (UserAchievement $userAchievement): UserAchievementResponse => UserAchievementResponse::fromUserAchievement($userAchievement),
            $this->userAchievements->allForUser($query->userId),
        );
    }
}
