<?php

declare(strict_types=1);

namespace Modules\Webhook\Domain\Entities;

use DateTimeImmutable;
use Modules\Webhook\Domain\Enums\WebhookDeliveryStatus;
use Modules\Webhook\Domain\Enums\WebhookEventName;
use Modules\Webhook\Domain\Exceptions\InvalidWebhookDeliveryRetry;

final class WebhookDelivery
{
    private const MAX_ATTEMPTS = 5;

    /** @param array<string, mixed> $payload */
    private function __construct(
        private string $id,
        private string $subscriptionId,
        private WebhookEventName $eventName,
        private array $payload,
        private WebhookDeliveryStatus $status,
        private int $attempts,
        private ?DateTimeImmutable $lastAttemptedAt,
        private ?int $lastResponseStatus,
        private ?string $lastResponseBody,
        private ?DateTimeImmutable $nextRetryAt,
        private DateTimeImmutable $createdAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function create(
        string $id,
        string $subscriptionId,
        WebhookEventName $eventName,
        array $payload,
        ?DateTimeImmutable $createdAt = null,
    ): self {
        return new self(
            $id,
            $subscriptionId,
            $eventName,
            $payload,
            WebhookDeliveryStatus::Pending,
            0,
            null,
            null,
            null,
            null,
            $createdAt ?? new DateTimeImmutable('now'),
        );
    }

    /** @param array<string, mixed> $payload */
    public static function restore(
        string $id,
        string $subscriptionId,
        WebhookEventName $eventName,
        array $payload,
        WebhookDeliveryStatus $status,
        int $attempts,
        ?DateTimeImmutable $lastAttemptedAt,
        ?int $lastResponseStatus,
        ?string $lastResponseBody,
        ?DateTimeImmutable $nextRetryAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            $id,
            $subscriptionId,
            $eventName,
            $payload,
            $status,
            $attempts,
            $lastAttemptedAt,
            $lastResponseStatus,
            $lastResponseBody,
            $nextRetryAt,
            $createdAt,
        );
    }

    public function markDelivered(int $responseStatus, ?string $responseBody, DateTimeImmutable $at): void
    {
        $this->status = WebhookDeliveryStatus::Delivered;
        $this->lastAttemptedAt = $at;
        $this->lastResponseStatus = $responseStatus;
        $this->lastResponseBody = $responseBody;
        $this->nextRetryAt = null;
    }

    public function recordFailedAttempt(?int $responseStatus, ?string $responseBody, DateTimeImmutable $at): void
    {
        $this->attempts++;
        $this->lastAttemptedAt = $at;
        $this->lastResponseStatus = $responseStatus;
        $this->lastResponseBody = $responseBody;

        if ($this->attempts >= self::MAX_ATTEMPTS) {
            $this->status = WebhookDeliveryStatus::DeadLettered;
            $this->nextRetryAt = null;

            return;
        }

        $this->status = WebhookDeliveryStatus::Failed;
        $this->nextRetryAt = $at->modify('+'.self::backoffSeconds($this->attempts).' seconds');
    }

    public function retryNow(): void
    {
        if (! in_array($this->status, [WebhookDeliveryStatus::Failed, WebhookDeliveryStatus::DeadLettered], true)) {
            throw InvalidWebhookDeliveryRetry::create();
        }

        $this->status = WebhookDeliveryStatus::Pending;
        $this->nextRetryAt = null;
    }

    public static function backoffSeconds(int $attempt): int
    {
        return min(3600, 30 * (2 ** ($attempt - 1)));
    }

    public function id(): string
    {
        return $this->id;
    }

    public function subscriptionId(): string
    {
        return $this->subscriptionId;
    }

    public function eventName(): WebhookEventName
    {
        return $this->eventName;
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->payload;
    }

    public function status(): WebhookDeliveryStatus
    {
        return $this->status;
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function lastAttemptedAt(): ?DateTimeImmutable
    {
        return $this->lastAttemptedAt;
    }

    public function lastResponseStatus(): ?int
    {
        return $this->lastResponseStatus;
    }

    public function lastResponseBody(): ?string
    {
        return $this->lastResponseBody;
    }

    public function nextRetryAt(): ?DateTimeImmutable
    {
        return $this->nextRetryAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
