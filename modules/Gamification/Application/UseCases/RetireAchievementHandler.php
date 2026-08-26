<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use DateTimeImmutable;
use Modules\Gamification\Application\Commands\RetireAchievementCommand;
use Modules\Gamification\Application\Exceptions\AchievementNotFound;
use Modules\Gamification\Application\Responses\AchievementResponse;
use Modules\Gamification\Domain\Repositories\AchievementRepository;
use Modules\Gamification\Domain\ValueObjects\AchievementId;

final readonly class RetireAchievementHandler
{
    public function __construct(private AchievementRepository $achievements) {}

    public function handle(RetireAchievementCommand $command): AchievementResponse
    {
        $achievement = $this->achievements->findById(AchievementId::fromString($command->achievementId));
        if ($achievement === null) {
            throw AchievementNotFound::withId($command->achievementId);
        }

        $achievement->retire($command->reason, new DateTimeImmutable('now'));
        $this->achievements->save($achievement);

        return AchievementResponse::fromAchievement($achievement);
    }
}
