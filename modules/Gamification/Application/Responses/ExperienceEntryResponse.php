<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Responses;

use DateTimeInterface;
use Modules\Gamification\Domain\Entities\ExperienceEntry;

final readonly class ExperienceEntryResponse
{
    public function __construct(
        public string $id,
        public string $userId,
        public int $points,
        public ?string $competencyId,
        public string $reason,
        public string $recordedAt,
    ) {}

    public static function fromExperienceEntry(ExperienceEntry $entry): self
    {
        return new self(
            id: $entry->id(),
            userId: $entry->userId(),
            points: $entry->points(),
            competencyId: $entry->competencyId(),
            reason: $entry->reason(),
            recordedAt: $entry->recordedAt()->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'points' => $this->points,
            'competency_id' => $this->competencyId,
            'reason' => $this->reason,
            'recorded_at' => $this->recordedAt,
        ];
    }
}
