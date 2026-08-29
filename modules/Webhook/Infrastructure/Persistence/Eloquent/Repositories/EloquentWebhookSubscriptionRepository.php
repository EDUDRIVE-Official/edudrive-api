<?php

declare(strict_types=1);

namespace Modules\Webhook\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\Crypt;
use Modules\Webhook\Domain\Aggregates\WebhookSubscription;
use Modules\Webhook\Domain\Enums\WebhookEventName;
use Modules\Webhook\Domain\Enums\WebhookSubscriptionStatus;
use Modules\Webhook\Domain\Repositories\WebhookSubscriptionRepository;
use Modules\Webhook\Domain\ValueObjects\WebhookSigningSecret;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;
use Modules\Webhook\Infrastructure\Persistence\Eloquent\Models\WebhookSubscriptionModel;

final readonly class EloquentWebhookSubscriptionRepository implements WebhookSubscriptionRepository
{
    public function save(WebhookSubscription $subscription): void
    {
        WebhookSubscriptionModel::query()->updateOrCreate(
            ['id' => $subscription->id()->value()],
            [
                'url' => $subscription->url(),
                'secret_encrypted' => Crypt::encryptString($subscription->secret()->value()),
                'events' => array_map(static fn (WebhookEventName $event): string => $event->value, $subscription->events()),
                'status' => $subscription->status()->value,
            ],
        );
    }

    public function findById(WebhookSubscriptionId $id): ?WebhookSubscription
    {
        $model = WebhookSubscriptionModel::query()->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<WebhookSubscription> */
    public function findActiveByEvent(WebhookEventName $event): array
    {
        return array_values(
            WebhookSubscriptionModel::query()
                ->where('status', WebhookSubscriptionStatus::Active->value)
                ->whereJsonContains('events', $event->value)
                ->orderBy('created_at')
                ->get()
                ->map(fn (WebhookSubscriptionModel $model): WebhookSubscription => $this->toDomain($model))
                ->all(),
        );
    }

    /** @return list<WebhookSubscription> */
    public function all(): array
    {
        return array_values(
            WebhookSubscriptionModel::query()
                ->orderBy('created_at')
                ->get()
                ->map(fn (WebhookSubscriptionModel $model): WebhookSubscription => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(WebhookSubscriptionModel $model): WebhookSubscription
    {
        /** @var list<string> $events */
        $events = $model->getAttribute('events') ?? [];

        return WebhookSubscription::restore(
            id: WebhookSubscriptionId::fromString((string) $model->getAttribute('id')),
            url: (string) $model->getAttribute('url'),
            events: array_map(static fn (string $event): WebhookEventName => WebhookEventName::from($event), $events),
            status: WebhookSubscriptionStatus::from((string) $model->getAttribute('status')),
            secret: WebhookSigningSecret::fromPlainValue(Crypt::decryptString((string) $model->getAttribute('secret_encrypted'))),
            createdAt: new DateTimeImmutable((string) $model->getAttribute('created_at')),
        );
    }
}
