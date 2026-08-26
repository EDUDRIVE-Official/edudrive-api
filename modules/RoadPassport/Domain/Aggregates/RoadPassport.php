<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Domain\Aggregates;

use DateTimeImmutable;
use Modules\RoadPassport\Domain\Enums\RoadPassportStatus;
use Modules\RoadPassport\Domain\Exceptions\InvalidRoadPassportLevel;
use Modules\RoadPassport\Domain\Exceptions\InvalidRoadPassportTransition;
use Modules\RoadPassport\Domain\ValueObjects\Evidence;
use Modules\RoadPassport\Domain\ValueObjects\PassportHistoryEntry;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;

final class RoadPassport
{
    /**
     * @param  list<PassportHistoryEntry>  $history
     * @param  list<Evidence>  $evidence
     */
    private function __construct(
        private RoadPassportId $id,
        private string $userId,
        private RoadPassportStatus $status,
        private int $level,
        private DateTimeImmutable $issuedAt,
        private array $history,
        private array $evidence = [],
    ) {}

    public static function create(RoadPassportId $id, string $userId, ?DateTimeImmutable $issuedAt = null): self
    {
        return new self(
            $id,
            $userId,
            RoadPassportStatus::Active,
            1,
            $issuedAt ?? new DateTimeImmutable('now'),
            [],
            [],
        );
    }

    /**
     * @param  list<PassportHistoryEntry>  $history
     * @param  list<Evidence>  $evidence
     */
    public static function restore(
        RoadPassportId $id,
        string $userId,
        RoadPassportStatus $status,
        int $level,
        DateTimeImmutable $issuedAt,
        array $history,
        array $evidence = [],
    ): self {
        return new self($id, $userId, $status, $level, $issuedAt, $history, $evidence);
    }

    public function suspend(?string $reason, DateTimeImmutable $at): void
    {
        if ($this->status !== RoadPassportStatus::Active) {
            throw InvalidRoadPassportTransition::create();
        }

        $this->transitionTo(RoadPassportStatus::Suspended, $reason, $at);
    }

    public function reactivate(DateTimeImmutable $at): void
    {
        if ($this->status !== RoadPassportStatus::Suspended) {
            throw InvalidRoadPassportTransition::create();
        }

        $this->transitionTo(RoadPassportStatus::Active, null, $at);
    }

    public function revoke(?string $reason, DateTimeImmutable $at): void
    {
        if ($this->status === RoadPassportStatus::Revoked) {
            throw InvalidRoadPassportTransition::create();
        }

        $this->transitionTo(RoadPassportStatus::Revoked, $reason, $at);
    }

    public function changeLevel(int $newLevel, DateTimeImmutable $at): void
    {
        if ($this->status !== RoadPassportStatus::Active) {
            throw InvalidRoadPassportLevel::create();
        }

        if ($newLevel <= $this->level) {
            throw InvalidRoadPassportLevel::create();
        }

        $this->history[] = PassportHistoryEntry::levelChanged($this->level, $newLevel, $at);
        $this->level = $newLevel;
    }

    public function id(): RoadPassportId
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function status(): RoadPassportStatus
    {
        return $this->status;
    }

    public function level(): int
    {
        return $this->level;
    }

    public function issuedAt(): DateTimeImmutable
    {
        return $this->issuedAt;
    }

    /** @return list<PassportHistoryEntry> */
    public function history(): array
    {
        return $this->history;
    }

    public function recordEvidence(Evidence $evidence): void
    {
        foreach ($this->evidence as $existing) {
            if ($existing->sameSubjectAs($evidence)) {
                return;
            }
        }

        $this->evidence[] = $evidence;
    }

    /** @return list<Evidence> */
    public function evidence(): array
    {
        return $this->evidence;
    }

    private function transitionTo(RoadPassportStatus $to, ?string $reason, DateTimeImmutable $at): void
    {
        $this->history[] = PassportHistoryEntry::statusChanged($this->status, $to, $at, $reason);
        $this->status = $to;
    }
}
