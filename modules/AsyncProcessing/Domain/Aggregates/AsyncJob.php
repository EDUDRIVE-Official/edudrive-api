<?php

declare(strict_types=1);

namespace Modules\AsyncProcessing\Domain\Aggregates;

use DateTimeImmutable;
use Modules\AsyncProcessing\Domain\Enums\AsyncJobStatus;
use Modules\AsyncProcessing\Domain\Exceptions\InvalidAsyncJobTransition;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

final class AsyncJob
{
    /** @param ?array<string, mixed> $result */
    private function __construct(
        private AsyncJobId $id,
        private string $type,
        private ?string $requestedByUserId,
        private AsyncJobStatus $status,
        private ?array $result,
        private ?string $failureReason,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $completedAt,
    ) {}

    public static function request(
        AsyncJobId $id,
        string $type,
        ?string $requestedByUserId,
        ?DateTimeImmutable $createdAt = null,
    ): self {
        return new self(
            $id,
            $type,
            $requestedByUserId,
            AsyncJobStatus::Pending,
            null,
            null,
            $createdAt ?? new DateTimeImmutable('now'),
            null,
            null,
        );
    }

    /** @param ?array<string, mixed> $result */
    public static function restore(
        AsyncJobId $id,
        string $type,
        ?string $requestedByUserId,
        AsyncJobStatus $status,
        ?array $result,
        ?string $failureReason,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $startedAt,
        ?DateTimeImmutable $completedAt,
    ): self {
        return new self($id, $type, $requestedByUserId, $status, $result, $failureReason, $createdAt, $startedAt, $completedAt);
    }

    public function start(DateTimeImmutable $at): void
    {
        if (in_array($this->status, [AsyncJobStatus::Completed, AsyncJobStatus::Failed], true)) {
            throw InvalidAsyncJobTransition::create();
        }

        $this->status = AsyncJobStatus::Processing;
        $this->startedAt ??= $at;
    }

    /** @param array<string, mixed> $result */
    public function complete(array $result, DateTimeImmutable $at): void
    {
        if (! in_array($this->status, [AsyncJobStatus::Pending, AsyncJobStatus::Processing], true)) {
            throw InvalidAsyncJobTransition::create();
        }

        $this->status = AsyncJobStatus::Completed;
        $this->result = $result;
        $this->completedAt = $at;
    }

    public function fail(string $reason, DateTimeImmutable $at): void
    {
        if (! in_array($this->status, [AsyncJobStatus::Pending, AsyncJobStatus::Processing], true)) {
            throw InvalidAsyncJobTransition::create();
        }

        $this->status = AsyncJobStatus::Failed;
        $this->failureReason = $reason;
        $this->completedAt = $at;
    }

    public function id(): AsyncJobId
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function requestedByUserId(): ?string
    {
        return $this->requestedByUserId;
    }

    public function status(): AsyncJobStatus
    {
        return $this->status;
    }

    /** @return ?array<string, mixed> */
    public function result(): ?array
    {
        return $this->result;
    }

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function startedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }
}
