<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Responses;

final readonly class DecisionPointsBatchResponse
{
    public function __construct(
        public int $decisionsRecorded,
    ) {}

    /** @return array{decisions_recorded: int} */
    public function toArray(): array
    {
        return [
            'decisions_recorded' => $this->decisionsRecorded,
        ];
    }
}
