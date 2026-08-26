<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Entities;

use DateTimeImmutable;

final readonly class UserBadge
{
    private function __construct(
        private string $id,
        private string $badgeId,
        private string $userId,
        private int $awardedVersion,
        private string $evidence,
        private DateTimeImmutable $earnedAt,
    ) {}

    public static function grant(
        string $id,
        string $badgeId,
        string $userId,
        int $awardedVersion,
        string $evidence,
        DateTimeImmutable $earnedAt,
    ): self {
        return new self($id, $badgeId, $userId, $awardedVersion, $evidence, $earnedAt);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function badgeId(): string
    {
        return $this->badgeId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function awardedVersion(): int
    {
        return $this->awardedVersion;
    }

    public function evidence(): string
    {
        return $this->evidence;
    }

    public function earnedAt(): DateTimeImmutable
    {
        return $this->earnedAt;
    }
}
