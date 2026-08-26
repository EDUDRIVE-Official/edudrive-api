<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Responses;

use DateTimeInterface;
use Modules\Gamification\Domain\Entities\UserBadge;

final readonly class UserBadgeResponse
{
    public function __construct(
        public string $badgeId,
        public string $userId,
        public int $awardedVersion,
        public string $evidence,
        public string $earnedAt,
    ) {}

    public static function fromUserBadge(UserBadge $userBadge): self
    {
        return new self(
            badgeId: $userBadge->badgeId(),
            userId: $userBadge->userId(),
            awardedVersion: $userBadge->awardedVersion(),
            evidence: $userBadge->evidence(),
            earnedAt: $userBadge->earnedAt()->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'badge_id' => $this->badgeId,
            'user_id' => $this->userId,
            'awarded_version' => $this->awardedVersion,
            'evidence' => $this->evidence,
            'earned_at' => $this->earnedAt,
        ];
    }
}
