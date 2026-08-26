<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Gamification\Application\Commands\GrantAchievementCommand;
use Modules\Gamification\Application\Exceptions\AchievementAlreadyGranted;
use Modules\Gamification\Application\Exceptions\AchievementNotAvailable;
use Modules\Gamification\Application\Exceptions\AchievementNotFound;
use Modules\Gamification\Application\Responses\UserAchievementResponse;
use Modules\Gamification\Domain\Entities\UserAchievement;
use Modules\Gamification\Domain\Enums\AchievementStatus;
use Modules\Gamification\Domain\Repositories\AchievementRepository;
use Modules\Gamification\Domain\Repositories\UserAchievementRepository;
use Modules\Gamification\Domain\ValueObjects\AchievementId;

final readonly class GrantAchievementHandler
{
    public function __construct(
        private AchievementRepository $achievements,
        private UserAchievementRepository $userAchievements,
    ) {}

    public function handle(GrantAchievementCommand $command): UserAchievementResponse
    {
        $achievement = $this->achievements->findById(AchievementId::fromString($command->achievementId));
        if ($achievement === null) {
            throw AchievementNotFound::withId($command->achievementId);
        }

        if ($achievement->status() !== AchievementStatus::Active) {
            throw AchievementNotAvailable::create();
        }

        if ($this->userAchievements->findByAchievementAndUser($command->achievementId, $command->userId) !== null) {
            throw AchievementAlreadyGranted::create();
        }

        $userAchievement = UserAchievement::grant(
            id: (string) Str::uuid(),
            achievementId: $command->achievementId,
            userId: $command->userId,
            evidence: $command->evidence,
            earnedAt: new DateTimeImmutable('now'),
        );

        $this->userAchievements->save($userAchievement);

        return UserAchievementResponse::fromUserAchievement($userAchievement);
    }
}
