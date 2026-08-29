<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Webhook\Application\Commands\RegisterWebhookSubscriptionCommand;
use Modules\Webhook\Application\Exceptions\InvalidWebhookEventName;
use Modules\Webhook\Application\Responses\WebhookSubscriptionResponse;
use Modules\Webhook\Domain\Aggregates\WebhookSubscription;
use Modules\Webhook\Domain\Enums\WebhookEventName;
use Modules\Webhook\Domain\Repositories\WebhookSubscriptionRepository;
use Modules\Webhook\Domain\ValueObjects\WebhookSigningSecret;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;

final readonly class RegisterWebhookSubscriptionHandler
{
    public function __construct(
        private WebhookSubscriptionRepository $subscriptions,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(RegisterWebhookSubscriptionCommand $command): WebhookSubscriptionResponse
    {
        $events = array_map(static function (string $event): WebhookEventName {
            $eventName = WebhookEventName::tryFrom($event);
            if ($eventName === null) {
                throw InvalidWebhookEventName::withValue($event);
            }

            return $eventName;
        }, $command->events);

        $secret = WebhookSigningSecret::generate();
        $subscription = WebhookSubscription::register(
            id: WebhookSubscriptionId::fromString((string) Str::uuid()),
            url: $command->url,
            events: $events,
            secret: $secret,
        );

        $this->subscriptions->save($subscription);

        $this->auditLogger->log(
            new AuditEntry(
                action: 'webhook.subscription_registered',
                userId: $command->actorId,
                entity: 'WebhookSubscription',
                entityId: $subscription->id()->value(),
                metadata: [
                    'url' => $command->url,
                    'events' => $command->events,
                ],
            ),
        );

        return WebhookSubscriptionResponse::fromSubscription($subscription, $secret->value());
    }
}
