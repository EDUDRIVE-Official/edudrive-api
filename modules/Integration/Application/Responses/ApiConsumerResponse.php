<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Responses;

use DateTimeInterface;
use Modules\Integration\Domain\Aggregates\ApiConsumer;
use Modules\Integration\Domain\ValueObjects\ApiConsumerHistoryEntry;

final readonly class ApiConsumerResponse
{
    /**
     * @param  list<string>  $scopes
     * @param  list<array{from: string, to: string, occurred_at: string, reason: ?string}>  $history
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $scopes,
        public string $status,
        public ?string $expiresAt,
        public string $createdAt,
        public array $history,
        public ?string $integrationKey = null,
    ) {}

    public static function fromApiConsumer(ApiConsumer $consumer, ?string $integrationKey = null): self
    {
        return new self(
            id: $consumer->id()->value(),
            name: $consumer->name(),
            scopes: $consumer->scopes(),
            status: $consumer->status()->value,
            expiresAt: $consumer->expiresAt()?->format(DateTimeInterface::ATOM),
            createdAt: $consumer->createdAt()->format(DateTimeInterface::ATOM),
            history: array_map(
                static fn (ApiConsumerHistoryEntry $entry): array => [
                    'from' => $entry->fromStatus->value,
                    'to' => $entry->toStatus->value,
                    'occurred_at' => $entry->occurredAt->format(DateTimeInterface::ATOM),
                    'reason' => $entry->reason,
                ],
                $consumer->history(),
            ),
            integrationKey: $integrationKey,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'scopes' => $this->scopes,
            'status' => $this->status,
            'expires_at' => $this->expiresAt,
            'created_at' => $this->createdAt,
            'history' => $this->history,
        ];

        if ($this->integrationKey !== null) {
            $data['integration_key'] = $this->integrationKey;
        }

        return $data;
    }
}
