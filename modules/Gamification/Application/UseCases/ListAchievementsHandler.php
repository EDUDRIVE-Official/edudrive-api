<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use Modules\Gamification\Application\Queries\ListAchievementsQuery;
use Modules\Gamification\Application\Responses\AchievementResponse;
use Modules\Gamification\Domain\Aggregates\Achievement;
use Modules\Gamification\Domain\Repositories\AchievementRepository;

final readonly class ListAchievementsHandler
{
    public function __construct(private AchievementRepository $achievements) {}

    /** @return list<AchievementResponse> */
    public function handle(ListAchievementsQuery $query): array
    {
        return array_map(
            static fn (Achievement $achievement): AchievementResponse => AchievementResponse::fromAchievement($achievement),
            $this->achievements->all(),
        );
    }
}
