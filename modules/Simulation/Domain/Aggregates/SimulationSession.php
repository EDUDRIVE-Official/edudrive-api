<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Simulation\Domain\Enums\SimulationSessionStatus;
use Modules\Simulation\Domain\Exceptions\InvalidSimulationSessionTransition;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionHistoryEntry;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;

final class SimulationSession
{
    /** @param list<SimulationSessionHistoryEntry> $history */
    private function __construct(
        private SimulationSessionId $id,
        private string $userId,
        private string $simulatorId,
        private string $vehicleType,
        private string $scenario,
        private DateTimeImmutable $scheduledAt,
        private int $plannedDurationMinutes,
        private SimulationSessionStatus $status,
        private ?DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $endedAt,
        private array $history,
    ) {}

    public static function schedule(
        SimulationSessionId $id,
        string $userId,
        string $simulatorId,
        string $vehicleType,
        string $scenario,
        DateTimeImmutable $scheduledAt,
        int $plannedDurationMinutes,
    ): self {
        return new self(
            $id,
            $userId,
            $simulatorId,
            $vehicleType,
            $scenario,
            $scheduledAt,
            $plannedDurationMinutes,
            SimulationSessionStatus::Scheduled,
            null,
            null,
            [],
        );
    }

    /** @param list<SimulationSessionHistoryEntry> $history */
    public static function restore(
        SimulationSessionId $id,
        string $userId,
        string $simulatorId,
        string $vehicleType,
        string $scenario,
        DateTimeImmutable $scheduledAt,
        int $plannedDurationMinutes,
        SimulationSessionStatus $status,
        ?DateTimeImmutable $startedAt,
        ?DateTimeImmutable $endedAt,
        array $history,
    ): self {
        return new self(
            $id,
            $userId,
            $simulatorId,
            $vehicleType,
            $scenario,
            $scheduledAt,
            $plannedDurationMinutes,
            $status,
            $startedAt,
            $endedAt,
            $history,
        );
    }

    public function start(DateTimeImmutable $at): void
    {
        if ($this->status !== SimulationSessionStatus::Scheduled) {
            throw InvalidSimulationSessionTransition::create();
        }

        $this->transitionTo(SimulationSessionStatus::InProgress, null, $at);
        $this->startedAt = $at;
    }

    public function complete(DateTimeImmutable $at): void
    {
        if ($this->status !== SimulationSessionStatus::InProgress) {
            throw InvalidSimulationSessionTransition::create();
        }

        $this->transitionTo(SimulationSessionStatus::Completed, null, $at);
        $this->endedAt = $at;
    }

    public function cancel(?string $reason, DateTimeImmutable $at): void
    {
        if ($this->status !== SimulationSessionStatus::Scheduled) {
            throw InvalidSimulationSessionTransition::create();
        }

        $this->transitionTo(SimulationSessionStatus::Cancelled, $reason, $at);
    }

    public function id(): SimulationSessionId
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function simulatorId(): string
    {
        return $this->simulatorId;
    }

    public function vehicleType(): string
    {
        return $this->vehicleType;
    }

    public function scenario(): string
    {
        return $this->scenario;
    }

    public function scheduledAt(): DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function plannedDurationMinutes(): int
    {
        return $this->plannedDurationMinutes;
    }

    public function status(): SimulationSessionStatus
    {
        return $this->status;
    }

    public function startedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function endedAt(): ?DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function actualDurationMinutes(): ?int
    {
        if ($this->startedAt === null || $this->endedAt === null) {
            return null;
        }

        return (int) round(($this->endedAt->getTimestamp() - $this->startedAt->getTimestamp()) / 60);
    }

    /** @return list<SimulationSessionHistoryEntry> */
    public function history(): array
    {
        return $this->history;
    }

    private function transitionTo(SimulationSessionStatus $to, ?string $reason, DateTimeImmutable $at): void
    {
        $this->history[] = SimulationSessionHistoryEntry::statusChanged($this->status, $to, $at, $reason);
        $this->status = $to;
    }
}
