<?php

declare(strict_types=1);

namespace Modules\Webhook\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Webhook\Domain\Enums\WebhookEventName;
use Modules\Webhook\Domain\Enums\WebhookSubscriptionStatus;
use Modules\Webhook\Domain\Exceptions\InvalidWebhookSubscriptionTransition;
use Modules\Webhook\Domain\ValueObjects\WebhookSigningSecret;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;

final class WebhookSubscription
{
    /** @param list<WebhookEventName> $events */
    private function __construct(
        private WebhookSubscriptionId $id,
        private string $url,
        private array $events,
        private WebhookSubscriptionStatus $status,
        private WebhookSigningSecret $secret,
        private DateTimeImmutable $createdAt,
    ) {}

    /** @param list<WebhookEventName> $events */
    public static function register(
        WebhookSubscriptionId $id,
        string $url,
        array $events,
        WebhookSigningSecret $secret,
        ?DateTimeImmutable $createdAt = null,
    ): self {
        return new self(
            $id,
            $url,
            $events,
            WebhookSubscriptionStatus::Active,
            $secret,
            $createdAt ?? new DateTimeImmutable('now'),
        );
    }

    /** @param list<WebhookEventName> $events */
    public static function restore(
        WebhookSubscriptionId $id,
        string $url,
        array $events,
        WebhookSubscriptionStatus $status,
        WebhookSigningSecret $secret,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $url, $events, $status, $secret, $createdAt);
    }

    public function suspend(): void
    {
        if ($this->status !== WebhookSubscriptionStatus::Active) {
            throw InvalidWebhookSubscriptionTransition::create();
        }

        $this->status = WebhookSubscriptionStatus::Suspended;
    }

    public function reactivate(): void
    {
        if ($this->status !== WebhookSubscriptionStatus::Suspended) {
            throw InvalidWebhookSubscriptionTransition::create();
        }

        $this->status = WebhookSubscriptionStatus::Active;
    }

    public function rotateSecret(WebhookSigningSecret $newSecret): void
    {
        $this->secret = $newSecret;
    }

    public function isActive(): bool
    {
        return $this->status === WebhookSubscriptionStatus::Active;
    }

    public function subscribesTo(WebhookEventName $event): bool
    {
        return in_array($event, $this->events, true);
    }

    public function id(): WebhookSubscriptionId
    {
        return $this->id;
    }

    public function url(): string
    {
        return $this->url;
    }

    /** @return list<WebhookEventName> */
    public function events(): array
    {
        return $this->events;
    }

    public function status(): WebhookSubscriptionStatus
    {
        return $this->status;
    }

    public function secret(): WebhookSigningSecret
    {
        return $this->secret;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
