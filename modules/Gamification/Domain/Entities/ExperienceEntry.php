<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class ExperienceEntry
{
    private function __construct(
        private string $id,
        private string $userId,
        private int $points,
        private ?string $competencyId,
        private string $reason,
        private DateTimeImmutable $recordedAt,
    ) {}

    public static function record(
        string $id,
        string $userId,
        int $points,
        ?string $competencyId,
        string $reason,
        DateTimeImmutable $recordedAt,
    ): self {
        return new self($id, $userId, self::normalizePoints($points), $competencyId, $reason, $recordedAt);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function points(): int
    {
        return $this->points;
    }

    public function competencyId(): ?string
    {
        return $this->competencyId;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function recordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }

    private static function normalizePoints(int $points): int
    {
        if ($points <= 0) {
            throw new InvalidArgumentException('Los puntos de experiencia deben ser un valor positivo.');
        }

        return $points;
    }
}
