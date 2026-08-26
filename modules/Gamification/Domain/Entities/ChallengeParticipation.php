<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Entities;

use DateTimeImmutable;
use Modules\Gamification\Domain\Enums\ChallengeParticipationStatus;
use Modules\Gamification\Domain\Exceptions\InvalidChallengeParticipationTransition;

final class ChallengeParticipation
{
    private function __construct(
        private string $id,
        private string $challengeId,
        private string $userId,
        private ChallengeParticipationStatus $status,
        private DateTimeImmutable $joinedAt,
        private ?DateTimeImmutable $completedAt,
        private ?string $evidence,
    ) {}

    public static function join(
        string $id,
        string $challengeId,
        string $userId,
        DateTimeImmutable $joinedAt,
    ): self {
        return new self($id, $challengeId, $userId, ChallengeParticipationStatus::Joined, $joinedAt, null, null);
    }

    public static function restore(
        string $id,
        string $challengeId,
        string $userId,
        ChallengeParticipationStatus $status,
        DateTimeImmutable $joinedAt,
        ?DateTimeImmutable $completedAt,
        ?string $evidence,
    ): self {
        return new self($id, $challengeId, $userId, $status, $joinedAt, $completedAt, $evidence);
    }

    public function complete(?string $evidence, DateTimeImmutable $at): void
    {
        if ($this->status === ChallengeParticipationStatus::Completed) {
            throw InvalidChallengeParticipationTransition::create();
        }

        $this->status = ChallengeParticipationStatus::Completed;
        $this->completedAt = $at;
        $this->evidence = $evidence;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function challengeId(): string
    {
        return $this->challengeId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function status(): ChallengeParticipationStatus
    {
        return $this->status;
    }

    public function joinedAt(): DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function evidence(): ?string
    {
        return $this->evidence;
    }
}
