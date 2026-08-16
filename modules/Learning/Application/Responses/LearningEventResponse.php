<?php

declare(strict_types=1);

namespace Modules\Learning\Application\Responses;

final readonly class LearningEventResponse
{
    /**
     * @param  list<array{verb: string, subject_id: string, occurred_at: string, evidence: array<string, mixed>}>  $events
     */
    public function __construct(
        public string $enrollmentId,
        public array $events,
    ) {}

    /**
     * @return array{enrollment_id: string, events: list<array{verb: string, subject_id: string, occurred_at: string, evidence: array<string, mixed>}>}
     */
    public function toArray(): array
    {
        return [
            'enrollment_id' => $this->enrollmentId,
            'events' => $this->events,
        ];
    }
}
