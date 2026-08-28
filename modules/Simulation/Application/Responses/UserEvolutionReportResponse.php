<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Responses;

final readonly class UserEvolutionReportResponse
{
    /** @param list<array{session_id: string, scenario: string, scheduled_at: string, score: int, outcome: string}> $entries */
    public function __construct(
        public string $userId,
        public array $entries,
    ) {}

    /** @return array{user_id: string, entries: list<array{session_id: string, scenario: string, scheduled_at: string, score: int, outcome: string}>} */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'entries' => $this->entries,
        ];
    }
}
