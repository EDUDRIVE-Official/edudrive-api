<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\ValueObjects;

use DateTimeImmutable;
use Modules\Simulation\Domain\Enums\SimulationSessionStatus;

final readonly class SimulationSessionHistoryEntry
{
    private function __construct(
        public SimulationSessionStatus $fromStatus,
        public SimulationSessionStatus $toStatus,
        public DateTimeImmutable $occurredAt,
        public ?string $reason,
    ) {}

    public static function statusChanged(
        SimulationSessionStatus $from,
        SimulationSessionStatus $to,
        DateTimeImmutable $occurredAt,
        ?string $reason,
    ): self {
        return new self($from, $to, $occurredAt, $reason);
    }

    public static function restore(
        SimulationSessionStatus $from,
        SimulationSessionStatus $to,
        DateTimeImmutable $occurredAt,
        ?string $reason,
    ): self {
        return new self($from, $to, $occurredAt, $reason);
    }
}
