<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Entities;

use DateTimeImmutable;
use Modules\Simulation\Domain\Enums\TelemetryEventType;

final readonly class TelemetryEvent
{
    private function __construct(
        private string $id,
        private string $sessionId,
        private TelemetryEventType $type,
        private ?string $details,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function record(
        string $id,
        string $sessionId,
        TelemetryEventType $type,
        ?string $details,
        DateTimeImmutable $occurredAt,
    ): self {
        return new self($id, $sessionId, $type, $details, $occurredAt);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function sessionId(): string
    {
        return $this->sessionId;
    }

    public function type(): TelemetryEventType
    {
        return $this->type;
    }

    public function details(): ?string
    {
        return $this->details;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
