<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Entities;

use DateTimeImmutable;

final readonly class UserAchievement
{
    private function __construct(
        private string $id,
        private string $achievementId,
        private string $userId,
        private string $evidence,
        private DateTimeImmutable $earnedAt,
    ) {}

    public static function grant(
        string $id,
        string $achievementId,
        string $userId,
        string $evidence,
        DateTimeImmutable $earnedAt,
    ): self {
        return new self($id, $achievementId, $userId, $evidence, $earnedAt);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function achievementId(): string
    {
        return $this->achievementId;
    }

    public function userId(): string
    {
        return $this->userId;
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
