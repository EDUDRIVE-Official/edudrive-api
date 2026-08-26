<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\ValueObjects;

use DateTimeImmutable;
use Modules\Simulation\Domain\Enums\TelemetryEventType;

final readonly class PracticalResultError
{
    public function __construct(
        public TelemetryEventType $type,
        public DateTimeImmutable $occurredAt,
        public int $penaltyPoints,
        public ?string $details,
    ) {}
}
