<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\Responses;

use DateTimeInterface;
use Modules\Webhook\Domain\Aggregates\WebhookSubscription;
use Modules\Webhook\Domain\Enums\WebhookEventName;

final readonly class WebhookSubscriptionResponse
{
    /** @param list<string> $events */
    public function __construct(
        public string $id,
        public string $url,
        public array $events,
        public string $status,
        public string $createdAt,
        public ?string $secret = null,
    ) {}

    public static function fromSubscription(WebhookSubscription $subscription, ?string $secret = null): self
    {
        return new self(
            id: $subscription->id()->value(),
            url: $subscription->url(),
            events: array_map(static fn (WebhookEventName $event): string => $event->value, $subscription->events()),
            status: $subscription->status()->value,
            createdAt: $subscription->createdAt()->format(DateTimeInterface::ATOM),
            secret: $secret,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'url' => $this->url,
            'events' => $this->events,
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];

        if ($this->secret !== null) {
            $data['secret'] = $this->secret;
        }

        return $data;
    }
}
