<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use Modules\Gamification\Application\Exceptions\AchievementNotFound;
use Modules\Gamification\Application\Queries\GetAchievementQuery;
use Modules\Gamification\Application\Responses\AchievementResponse;
use Modules\Gamification\Domain\Repositories\AchievementRepository;
use Modules\Gamification\Domain\ValueObjects\AchievementId;

final readonly class GetAchievementHandler
{
    public function __construct(private AchievementRepository $achievements) {}

    public function handle(GetAchievementQuery $query): AchievementResponse
    {
        $achievement = $this->achievements->findById(AchievementId::fromString($query->achievementId));
        if ($achievement === null) {
            throw AchievementNotFound::withId($query->achievementId);
        }

        return AchievementResponse::fromAchievement($achievement);
    }
}
