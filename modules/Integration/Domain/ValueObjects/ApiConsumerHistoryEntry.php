<?php

declare(strict_types=1);

namespace Modules\Integration\Domain\ValueObjects;

use DateTimeImmutable;
use Modules\Integration\Domain\Enums\ApiConsumerStatus;

final readonly class ApiConsumerHistoryEntry
{
    private function __construct(
        public ApiConsumerStatus $fromStatus,
        public ApiConsumerStatus $toStatus,
        public DateTimeImmutable $occurredAt,
        public ?string $reason,
    ) {}

    public static function statusChanged(
        ApiConsumerStatus $from,
        ApiConsumerStatus $to,
        DateTimeImmutable $occurredAt,
        ?string $reason,
    ): self {
        return new self($from, $to, $occurredAt, $reason);
    }

    public static function restore(
        ApiConsumerStatus $from,
        ApiConsumerStatus $to,
        DateTimeImmutable $occurredAt,
        ?string $reason,
    ): self {
        return new self($from, $to, $occurredAt, $reason);
    }
}
