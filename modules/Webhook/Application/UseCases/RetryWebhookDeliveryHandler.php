<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\UseCases;

use Modules\Webhook\Application\Commands\RetryWebhookDeliveryCommand;
use Modules\Webhook\Application\Exceptions\WebhookDeliveryNotFound;
use Modules\Webhook\Application\Responses\WebhookDeliveryResponse;
use Modules\Webhook\Application\Services\WebhookDeliveryDispatcher;
use Modules\Webhook\Domain\Repositories\WebhookDeliveryRepository;

final readonly class RetryWebhookDeliveryHandler
{
    public function __construct(
        private WebhookDeliveryRepository $deliveries,
        private WebhookDeliveryDispatcher $dispatcher,
    ) {}

    public function handle(RetryWebhookDeliveryCommand $command): WebhookDeliveryResponse
    {
        $delivery = $this->deliveries->findById($command->deliveryId);
        if ($delivery === null) {
            throw WebhookDeliveryNotFound::withId($command->deliveryId);
        }

        $delivery->retryNow();
        $this->deliveries->save($delivery);
        $this->dispatcher->dispatch($delivery->id());

        return WebhookDeliveryResponse::fromDelivery($delivery);
    }
}
