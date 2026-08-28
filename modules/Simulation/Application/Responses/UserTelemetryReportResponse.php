<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Responses;

final readonly class UserTelemetryReportResponse
{
    /** @param array<string, int> $eventCountsByType */
    public function __construct(
        public string $userId,
        public int $sessionCount,
        public int $totalEvents,
        public array $eventCountsByType,
    ) {}

    /** @return array{user_id: string, session_count: int, total_events: int, event_counts_by_type: array<string, int>} */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'session_count' => $this->sessionCount,
            'total_events' => $this->totalEvents,
            'event_counts_by_type' => $this->eventCountsByType,
        ];
    }
}
