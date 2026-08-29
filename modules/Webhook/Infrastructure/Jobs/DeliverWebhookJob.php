<?php

declare(strict_types=1);

namespace Modules\Webhook\Infrastructure\Jobs;

use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Webhook\Application\Services\WebhookDeliveryDispatcher;
use Modules\Webhook\Domain\Entities\WebhookDelivery;
use Modules\Webhook\Domain\Enums\WebhookDeliveryStatus;
use Modules\Webhook\Domain\Repositories\WebhookDeliveryRepository;
use Modules\Webhook\Domain\Repositories\WebhookSubscriptionRepository;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;
use Throwable;

final class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly string $deliveryId,
    ) {}

    public function handle(
        WebhookDeliveryRepository $deliveries,
        WebhookSubscriptionRepository $subscriptions,
        WebhookDeliveryDispatcher $dispatcher,
    ): void {
        $delivery = $deliveries->findById($this->deliveryId);
        if ($delivery === null) {
            return;
        }

        $subscription = $subscriptions->findById(WebhookSubscriptionId::fromString($delivery->subscriptionId()));
        $at = now()->toDateTimeImmutable();

        if ($subscription === null || ! $subscription->isActive()) {
            $delivery->recordFailedAttempt(null, 'La suscripcion no esta activa.', $at);
            $deliveries->save($delivery);
            $this->reschedule($delivery, $dispatcher);

            return;
        }

        $body = [
            'event' => $delivery->eventName()->value,
            'occurred_at' => $delivery->createdAt()->format(DateTimeInterface::ATOM),
            'data' => $delivery->payload(),
        ];
        $payloadJson = json_encode($body, JSON_THROW_ON_ERROR);
        $signature = $subscription->secret()->sign($payloadJson);

        try {
            $response = Http::withBody($payloadJson, 'application/json')
                ->withHeaders([
                    'X-Webhook-Signature' => 'sha256='.$signature,
                    'X-Webhook-Delivery-Id' => $delivery->id(),
                    'X-Webhook-Event' => $delivery->eventName()->value,
                ])
                ->timeout(5)
                ->post($subscription->url());

            if ($response->successful()) {
                $delivery->markDelivered($response->status(), Str::limit($response->body(), 1000), $at);
                $deliveries->save($delivery);

                return;
            }

            $delivery->recordFailedAttempt($response->status(), Str::limit($response->body(), 1000), $at);
        } catch (Throwable $exception) {
            $delivery->recordFailedAttempt(null, Str::limit($exception->getMessage(), 1000), $at);
        }

        $deliveries->save($delivery);
        $this->reschedule($delivery, $dispatcher);
    }

    private function reschedule(WebhookDelivery $delivery, WebhookDeliveryDispatcher $dispatcher): void
    {
        if ($delivery->status() !== WebhookDeliveryStatus::Failed) {
            return;
        }

        $dispatcher->dispatch($delivery->id(), WebhookDelivery::backoffSeconds($delivery->attempts()));
    }
}
