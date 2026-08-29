<?php

declare(strict_types=1);

namespace Modules\Webhook\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Webhook\Domain\Entities\WebhookDelivery;
use Modules\Webhook\Domain\Enums\WebhookDeliveryStatus;
use Modules\Webhook\Domain\Enums\WebhookEventName;
use Modules\Webhook\Domain\Repositories\WebhookDeliveryRepository;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;
use Modules\Webhook\Infrastructure\Persistence\Eloquent\Models\WebhookDeliveryModel;

final readonly class EloquentWebhookDeliveryRepository implements WebhookDeliveryRepository
{
    public function save(WebhookDelivery $delivery): void
    {
        WebhookDeliveryModel::query()->updateOrCreate(
            ['id' => $delivery->id()],
            [
                'subscription_id' => $delivery->subscriptionId(),
                'event_name' => $delivery->eventName()->value,
                'payload' => $delivery->payload(),
                'status' => $delivery->status()->value,
                'attempts' => $delivery->attempts(),
                'last_attempted_at' => $delivery->lastAttemptedAt(),
                'last_response_status' => $delivery->lastResponseStatus(),
                'last_response_body' => $delivery->lastResponseBody(),
                'next_retry_at' => $delivery->nextRetryAt(),
            ],
        );
    }

    public function findById(string $id): ?WebhookDelivery
    {
        $model = WebhookDeliveryModel::query()->where('id', $id)->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<WebhookDelivery> */
    public function findBySubscription(WebhookSubscriptionId $subscriptionId, ?WebhookDeliveryStatus $status = null): array
    {
        $query = WebhookDeliveryModel::query()
            ->where('subscription_id', $subscriptionId->value())
            ->orderBy('created_at', 'desc');

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return array_values(
            $query->get()
                ->map(fn (WebhookDeliveryModel $model): WebhookDelivery => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(WebhookDeliveryModel $model): WebhookDelivery
    {
        $lastAttemptedAt = $model->getAttribute('last_attempted_at');
        $nextRetryAt = $model->getAttribute('next_retry_at');
        $lastResponseStatus = $model->getAttribute('last_response_status');
        $lastResponseBody = $model->getAttribute('last_response_body');

        return WebhookDelivery::restore(
            id: (string) $model->getAttribute('id'),
            subscriptionId: (string) $model->getAttribute('subscription_id'),
            eventName: WebhookEventName::from((string) $model->getAttribute('event_name')),
            payload: $model->getAttribute('payload') ?? [],
            status: WebhookDeliveryStatus::from((string) $model->getAttribute('status')),
            attempts: (int) $model->getAttribute('attempts'),
            lastAttemptedAt: $lastAttemptedAt === null ? null : new DateTimeImmutable((string) $lastAttemptedAt),
            lastResponseStatus: $lastResponseStatus === null ? null : (int) $lastResponseStatus,
            lastResponseBody: $lastResponseBody === null ? null : (string) $lastResponseBody,
            nextRetryAt: $nextRetryAt === null ? null : new DateTimeImmutable((string) $nextRetryAt),
            createdAt: new DateTimeImmutable((string) $model->getAttribute('created_at')),
        );
    }
}
