<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Responses;

use DateTimeInterface;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionHistoryEntry;

final readonly class SimulationSessionResponse
{
    /**
     * @param  list<array{from: string, to: string, occurred_at: string, reason: ?string}>  $history
     */
    public function __construct(
        public string $id,
        public string $userId,
        public string $simulatorId,
        public string $vehicleType,
        public string $scenario,
        public string $scheduledAt,
        public int $plannedDurationMinutes,
        public ?int $actualDurationMinutes,
        public string $status,
        public ?string $startedAt,
        public ?string $endedAt,
        public array $history,
    ) {}

    public static function fromSimulationSession(SimulationSession $session): self
    {
        return new self(
            id: $session->id()->value(),
            userId: $session->userId(),
            simulatorId: $session->simulatorId(),
            vehicleType: $session->vehicleType(),
            scenario: $session->scenario(),
            scheduledAt: $session->scheduledAt()->format(DateTimeInterface::ATOM),
            plannedDurationMinutes: $session->plannedDurationMinutes(),
            actualDurationMinutes: $session->actualDurationMinutes(),
            status: $session->status()->value,
            startedAt: $session->startedAt()?->format(DateTimeInterface::ATOM),
            endedAt: $session->endedAt()?->format(DateTimeInterface::ATOM),
            history: array_map(
                static fn (SimulationSessionHistoryEntry $entry): array => [
                    'from' => $entry->fromStatus->value,
                    'to' => $entry->toStatus->value,
                    'occurred_at' => $entry->occurredAt->format(DateTimeInterface::ATOM),
                    'reason' => $entry->reason,
                ],
                $session->history(),
            ),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'simulator_id' => $this->simulatorId,
            'vehicle_type' => $this->vehicleType,
            'scenario' => $this->scenario,
            'scheduled_at' => $this->scheduledAt,
            'planned_duration_minutes' => $this->plannedDurationMinutes,
            'actual_duration_minutes' => $this->actualDurationMinutes,
            'status' => $this->status,
            'started_at' => $this->startedAt,
            'ended_at' => $this->endedAt,
            'history' => $this->history,
        ];
    }
}
