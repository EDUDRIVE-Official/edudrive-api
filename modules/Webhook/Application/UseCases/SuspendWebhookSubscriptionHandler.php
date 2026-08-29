<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\UseCases;

use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Webhook\Application\Commands\SuspendWebhookSubscriptionCommand;
use Modules\Webhook\Application\Exceptions\WebhookSubscriptionNotFound;
use Modules\Webhook\Application\Responses\WebhookSubscriptionResponse;
use Modules\Webhook\Domain\Repositories\WebhookSubscriptionRepository;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;

final readonly class SuspendWebhookSubscriptionHandler
{
    public function __construct(
        private WebhookSubscriptionRepository $subscriptions,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(SuspendWebhookSubscriptionCommand $command): WebhookSubscriptionResponse
    {
        $subscription = $this->subscriptions->findById(WebhookSubscriptionId::fromString($command->subscriptionId));
        if ($subscription === null) {
            throw WebhookSubscriptionNotFound::withId($command->subscriptionId);
        }

        $subscription->suspend();
        $this->subscriptions->save($subscription);

        $this->auditLogger->log(
            new AuditEntry(
                action: 'webhook.subscription_suspended',
                userId: $command->actorId,
                entity: 'WebhookSubscription',
                entityId: $subscription->id()->value(),
            ),
        );

        return WebhookSubscriptionResponse::fromSubscription($subscription);
    }
}
