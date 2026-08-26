<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Responses;

use DateTimeInterface;
use Modules\Gamification\Domain\Entities\ChallengeParticipation;

final readonly class ChallengeParticipationResponse
{
    public function __construct(
        public string $id,
        public string $challengeId,
        public string $userId,
        public string $status,
        public string $joinedAt,
        public ?string $completedAt,
        public ?string $evidence,
    ) {}

    public static function fromChallengeParticipation(ChallengeParticipation $participation): self
    {
        return new self(
            id: $participation->id(),
            challengeId: $participation->challengeId(),
            userId: $participation->userId(),
            status: $participation->status()->value,
            joinedAt: $participation->joinedAt()->format(DateTimeInterface::ATOM),
            completedAt: $participation->completedAt()?->format(DateTimeInterface::ATOM),
            evidence: $participation->evidence(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'challenge_id' => $this->challengeId,
            'user_id' => $this->userId,
            'status' => $this->status,
            'joined_at' => $this->joinedAt,
            'completed_at' => $this->completedAt,
            'evidence' => $this->evidence,
        ];
    }
}
