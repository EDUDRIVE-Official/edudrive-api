<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Responses;

final readonly class UserSessionsReportResponse
{
    public function __construct(
        public string $userId,
        public int $sessionCount,
        public int $completedCount,
        public int $cancelledCount,
        public ?float $averageDurationMinutes,
    ) {}

    /** @return array{user_id: string, session_count: int, completed_count: int, cancelled_count: int, average_duration_minutes: float|null} */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'session_count' => $this->sessionCount,
            'completed_count' => $this->completedCount,
            'cancelled_count' => $this->cancelledCount,
            'average_duration_minutes' => $this->averageDurationMinutes,
        ];
    }
}
