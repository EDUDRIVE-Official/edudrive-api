<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Responses;

use DateTimeInterface;
use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\ValueObjects\SimulatorHistoryEntry;

final readonly class SimulatorResponse
{
    /**
     * @param  list<array{from: string, to: string, occurred_at: string, reason: ?string}>  $history
     */
    public function __construct(
        public string $id,
        public string $deviceIdentifier,
        public string $softwareVersion,
        public ?string $location,
        public string $status,
        public string $registeredAt,
        public array $history,
        public ?string $integrationKey = null,
    ) {}

    public static function fromSimulator(Simulator $simulator, ?string $integrationKey = null): self
    {
        return new self(
            id: $simulator->id()->value(),
            deviceIdentifier: $simulator->deviceIdentifier()->value(),
            softwareVersion: $simulator->softwareVersion(),
            location: $simulator->location(),
            status: $simulator->status()->value,
            registeredAt: $simulator->registeredAt()->format(DateTimeInterface::ATOM),
            history: array_map(
                static fn (SimulatorHistoryEntry $entry): array => [
                    'from' => $entry->fromStatus->value,
                    'to' => $entry->toStatus->value,
                    'occurred_at' => $entry->occurredAt->format(DateTimeInterface::ATOM),
                    'reason' => $entry->reason,
                ],
                $simulator->history(),
            ),
            integrationKey: $integrationKey,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'device_identifier' => $this->deviceIdentifier,
            'software_version' => $this->softwareVersion,
            'location' => $this->location,
            'status' => $this->status,
            'registered_at' => $this->registeredAt,
            'history' => $this->history,
        ];

        if ($this->integrationKey !== null) {
            $data['integration_key'] = $this->integrationKey;
        }

        return $data;
    }
}
