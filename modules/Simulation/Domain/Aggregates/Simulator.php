<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Simulation\Domain\Enums\SimulatorStatus;
use Modules\Simulation\Domain\Exceptions\InvalidSimulatorTransition;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulatorHistoryEntry;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;

final class Simulator
{
    /** @param list<SimulatorHistoryEntry> $history */
    private function __construct(
        private SimulatorId $id,
        private DeviceIdentifier $deviceIdentifier,
        private string $softwareVersion,
        private ?string $location,
        private SimulatorStatus $status,
        private IntegrationKey $integrationKey,
        private DateTimeImmutable $registeredAt,
        private array $history,
    ) {}

    public static function register(
        SimulatorId $id,
        DeviceIdentifier $deviceIdentifier,
        string $softwareVersion,
        ?string $location,
        IntegrationKey $integrationKey,
        ?DateTimeImmutable $registeredAt = null,
    ): self {
        return new self(
            $id,
            $deviceIdentifier,
            $softwareVersion,
            $location,
            SimulatorStatus::Active,
            $integrationKey,
            $registeredAt ?? new DateTimeImmutable('now'),
            [],
        );
    }

    /** @param list<SimulatorHistoryEntry> $history */
    public static function restore(
        SimulatorId $id,
        DeviceIdentifier $deviceIdentifier,
        string $softwareVersion,
        ?string $location,
        SimulatorStatus $status,
        IntegrationKey $integrationKey,
        DateTimeImmutable $registeredAt,
        array $history,
    ): self {
        return new self($id, $deviceIdentifier, $softwareVersion, $location, $status, $integrationKey, $registeredAt, $history);
    }

    public function suspend(?string $reason, DateTimeImmutable $at): void
    {
        if ($this->status !== SimulatorStatus::Active) {
            throw InvalidSimulatorTransition::create();
        }

        $this->transitionTo(SimulatorStatus::Suspended, $reason, $at);
    }

    public function reactivate(DateTimeImmutable $at): void
    {
        if ($this->status !== SimulatorStatus::Suspended) {
            throw InvalidSimulatorTransition::create();
        }

        $this->transitionTo(SimulatorStatus::Active, null, $at);
    }

    public function retire(?string $reason, DateTimeImmutable $at): void
    {
        if ($this->status === SimulatorStatus::Retired) {
            throw InvalidSimulatorTransition::create();
        }

        $this->transitionTo(SimulatorStatus::Retired, $reason, $at);
    }

    public function rotateIntegrationKey(IntegrationKey $newKey): void
    {
        $this->integrationKey = $newKey;
    }

    public function id(): SimulatorId
    {
        return $this->id;
    }

    public function deviceIdentifier(): DeviceIdentifier
    {
        return $this->deviceIdentifier;
    }

    public function softwareVersion(): string
    {
        return $this->softwareVersion;
    }

    public function location(): ?string
    {
        return $this->location;
    }

    public function status(): SimulatorStatus
    {
        return $this->status;
    }

    public function integrationKey(): IntegrationKey
    {
        return $this->integrationKey;
    }

    public function registeredAt(): DateTimeImmutable
    {
        return $this->registeredAt;
    }

    /** @return list<SimulatorHistoryEntry> */
    public function history(): array
    {
        return $this->history;
    }

    private function transitionTo(SimulatorStatus $to, ?string $reason, DateTimeImmutable $at): void
    {
        $this->history[] = SimulatorHistoryEntry::statusChanged($this->status, $to, $at, $reason);
        $this->status = $to;
    }
}
