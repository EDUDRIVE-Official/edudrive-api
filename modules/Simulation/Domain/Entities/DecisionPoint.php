<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Entities;

use DateTimeImmutable;
use Modules\Simulation\Domain\Enums\DecisionRiskLevel;
use Modules\Simulation\Domain\Enums\DriverReactionType;

final readonly class DecisionPoint
{
    private function __construct(
        private string $id,
        private string $sessionId,
        private string $roadContext,
        private DecisionRiskLevel $riskLevel,
        private DriverReactionType $driverReaction,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function record(
        string $id,
        string $sessionId,
        string $roadContext,
        DecisionRiskLevel $riskLevel,
        DriverReactionType $driverReaction,
        DateTimeImmutable $occurredAt,
    ): self {
        return new self($id, $sessionId, $roadContext, $riskLevel, $driverReaction, $occurredAt);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function sessionId(): string
    {
        return $this->sessionId;
    }

    public function roadContext(): string
    {
        return $this->roadContext;
    }

    public function riskLevel(): DecisionRiskLevel
    {
        return $this->riskLevel;
    }

    public function driverReaction(): DriverReactionType
    {
        return $this->driverReaction;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
