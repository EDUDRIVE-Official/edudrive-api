<?php

declare(strict_types=1);

namespace Modules\AsyncProcessing\Application\Responses;

use DateTimeInterface;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;

final readonly class AsyncJobResponse
{
    /** @param ?array<string, mixed> $result */
    public function __construct(
        public string $id,
        public string $type,
        public string $status,
        public ?array $result,
        public ?string $failureReason,
        public string $createdAt,
        public ?string $startedAt,
        public ?string $completedAt,
    ) {}

    public static function fromJob(AsyncJob $job): self
    {
        return new self(
            id: $job->id()->value(),
            type: $job->type(),
            status: $job->status()->value,
            result: $job->result(),
            failureReason: $job->failureReason(),
            createdAt: $job->createdAt()->format(DateTimeInterface::ATOM),
            startedAt: $job->startedAt()?->format(DateTimeInterface::ATOM),
            completedAt: $job->completedAt()?->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'result' => $this->result,
            'failure_reason' => $this->failureReason,
            'created_at' => $this->createdAt,
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt,
        ];
    }
}
