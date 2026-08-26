<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Domain\ValueObjects;

use DateTimeImmutable;
use Modules\RoadPassport\Domain\Enums\RoadPassportHistoryType;
use Modules\RoadPassport\Domain\Enums\RoadPassportStatus;

final readonly class PassportHistoryEntry
{
    private function __construct(
        public RoadPassportHistoryType $type,
        public string $fromValue,
        public string $toValue,
        public DateTimeImmutable $occurredAt,
        public ?string $reason,
    ) {}

    public static function statusChanged(
        RoadPassportStatus $from,
        RoadPassportStatus $to,
        DateTimeImmutable $occurredAt,
        ?string $reason,
    ): self {
        return new self(RoadPassportHistoryType::StatusChanged, $from->value, $to->value, $occurredAt, $reason);
    }

    public static function levelChanged(int $from, int $to, DateTimeImmutable $occurredAt): self
    {
        return new self(RoadPassportHistoryType::LevelChanged, (string) $from, (string) $to, $occurredAt, null);
    }

    public static function restore(
        RoadPassportHistoryType $type,
        string $fromValue,
        string $toValue,
        DateTimeImmutable $occurredAt,
        ?string $reason,
    ): self {
        return new self($type, $fromValue, $toValue, $occurredAt, $reason);
    }
}
