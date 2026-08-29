<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\Responses;

use DateTimeInterface;
use Modules\Webhook\Domain\Entities\WebhookDelivery;

final readonly class WebhookDeliveryResponse
{
    public function __construct(
        public string $id,
        public string $subscriptionId,
        public string $eventName,
        public string $status,
        public int $attempts,
        public ?string $lastAttemptedAt,
        public ?int $lastResponseStatus,
        public ?string $lastResponseBody,
        public ?string $nextRetryAt,
        public string $createdAt,
    ) {}

    public static function fromDelivery(WebhookDelivery $delivery): self
    {
        return new self(
            id: $delivery->id(),
            subscriptionId: $delivery->subscriptionId(),
            eventName: $delivery->eventName()->value,
            status: $delivery->status()->value,
            attempts: $delivery->attempts(),
            lastAttemptedAt: $delivery->lastAttemptedAt()?->format(DateTimeInterface::ATOM),
            lastResponseStatus: $delivery->lastResponseStatus(),
            lastResponseBody: $delivery->lastResponseBody(),
            nextRetryAt: $delivery->nextRetryAt()?->format(DateTimeInterface::ATOM),
            createdAt: $delivery->createdAt()->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscriptionId,
            'event_name' => $this->eventName,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'last_attempted_at' => $this->lastAttemptedAt,
            'last_response_status' => $this->lastResponseStatus,
            'last_response_body' => $this->lastResponseBody,
            'next_retry_at' => $this->nextRetryAt,
            'created_at' => $this->createdAt,
        ];
    }
}
