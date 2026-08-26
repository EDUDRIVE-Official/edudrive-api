<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Responses;

final readonly class TelemetryBatchResponse
{
    public function __construct(
        public int $samplesRecorded,
        public int $eventsRecorded,
    ) {}

    /** @return array{samples_recorded: int, events_recorded: int} */
    public function toArray(): array
    {
        return [
            'samples_recorded' => $this->samplesRecorded,
            'events_recorded' => $this->eventsRecorded,
        ];
    }
}
