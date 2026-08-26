<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\ValueObjects;

use DateTimeImmutable;
use Modules\Simulation\Domain\Enums\SimulatorStatus;

final readonly class SimulatorHistoryEntry
{
    private function __construct(
        public SimulatorStatus $fromStatus,
        public SimulatorStatus $toStatus,
        public DateTimeImmutable $occurredAt,
        public ?string $reason,
    ) {}

    public static function statusChanged(
        SimulatorStatus $from,
        SimulatorStatus $to,
        DateTimeImmutable $occurredAt,
        ?string $reason,
    ): self {
        return new self($from, $to, $occurredAt, $reason);
    }

    public static function restore(
        SimulatorStatus $from,
        SimulatorStatus $to,
        DateTimeImmutable $occurredAt,
        ?string $reason,
    ): self {
        return new self($from, $to, $occurredAt, $reason);
    }
}
