<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Responses;

use DateTimeInterface;
use Modules\Gamification\Domain\Entities\UserAchievement;

final readonly class UserAchievementResponse
{
    public function __construct(
        public string $achievementId,
        public string $userId,
        public string $evidence,
        public string $earnedAt,
    ) {}

    public static function fromUserAchievement(UserAchievement $userAchievement): self
    {
        return new self(
            achievementId: $userAchievement->achievementId(),
            userId: $userAchievement->userId(),
            evidence: $userAchievement->evidence(),
            earnedAt: $userAchievement->earnedAt()->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'achievement_id' => $this->achievementId,
            'user_id' => $this->userId,
            'evidence' => $this->evidence,
            'earned_at' => $this->earnedAt,
        ];
    }
}
