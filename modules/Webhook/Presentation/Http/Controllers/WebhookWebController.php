<?php

declare(strict_types=1);

namespace Modules\Webhook\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Foundation\Domain\Exceptions\DomainException;
use Modules\Webhook\Application\Commands\ReactivateWebhookSubscriptionCommand;
use Modules\Webhook\Application\Commands\RegisterWebhookSubscriptionCommand;
use Modules\Webhook\Application\Commands\RetryWebhookDeliveryCommand;
use Modules\Webhook\Application\Commands\RotateWebhookSubscriptionSecretCommand;
use Modules\Webhook\Application\Commands\SuspendWebhookSubscriptionCommand;
use Modules\Webhook\Application\Queries\GetWebhookSubscriptionQuery;
use Modules\Webhook\Application\Queries\ListWebhookDeliveriesQuery;
use Modules\Webhook\Application\Queries\ListWebhookSubscriptionsQuery;
use Modules\Webhook\Application\Responses\WebhookDeliveryResponse;
use Modules\Webhook\Application\Responses\WebhookSubscriptionResponse;
use Modules\Webhook\Domain\Enums\WebhookDeliveryStatus;
use Modules\Webhook\Domain\Enums\WebhookEventName;
use Modules\Webhook\Presentation\Http\Requests\ListWebhookDeliveriesRequest;
use Modules\Webhook\Presentation\Http\Requests\RegisterWebhookSubscriptionRequest;

final class WebhookWebController
{
    private const array EVENT_LABELS = [
        'enrollment.created' => 'Nueva matrícula creada',
        'certificate.issued' => 'Nuevo certificado emitido',
    ];

    public function index(QueryBus $queryBus): View
    {
        $subscriptions = $queryBus->ask(new ListWebhookSubscriptionsQuery);
        assert(is_array($subscriptions));

        return view('webhooks.index', [
            'subscriptions' => array_map(static fn (WebhookSubscriptionResponse $subscription): array => $subscription->toArray(), $subscriptions),
            'events' => WebhookEventName::cases(),
            'eventLabels' => self::EVENT_LABELS,
        ]);
    }

    public function deliveries(string $subscriptionId, ListWebhookDeliveriesRequest $request, QueryBus $queryBus): View
    {
        $data = $request->validated();
        $subscription = $queryBus->ask(new GetWebhookSubscriptionQuery(subscriptionId: $subscriptionId));
        $deliveries = $queryBus->ask(new ListWebhookDeliveriesQuery(
            subscriptionId: $subscriptionId,
            status: isset($data['status']) ? (string) $data['status'] : null,
        ));
        assert($subscription instanceof WebhookSubscriptionResponse);
        assert(is_array($deliveries));

        return view('webhooks.deliveries', [
            'subscription' => $subscription->toArray(),
            'deliveries' => array_map(static fn (WebhookDeliveryResponse $delivery): array => $delivery->toArray(), $deliveries),
            'statuses' => WebhookDeliveryStatus::cases(),
            'selectedStatus' => $data['status'] ?? null,
            'eventLabels' => self::EVENT_LABELS,
        ]);
    }

    public function store(RegisterWebhookSubscriptionRequest $request, CommandBus $commandBus): RedirectResponse
    {
        $data = $request->validated();

        try {
            $result = $commandBus->dispatch(new RegisterWebhookSubscriptionCommand(
                url: (string) $data['url'],
                events: $data['events'],
                actorId: (string) $request->user()?->getAuthIdentifier(),
            ));
            assert($result instanceof WebhookSubscriptionResponse);
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Webhook registrado. Copiá el secreto ahora: no volverá a mostrarse.')
            ->with('webhook_secret', $result->secret);
    }

    public function suspend(string $subscriptionId, Request $request, CommandBus $commandBus): RedirectResponse
    {
        return $this->transition(fn (): mixed => $commandBus->dispatch(new SuspendWebhookSubscriptionCommand(
            subscriptionId: $subscriptionId,
            actorId: (string) $request->user()?->getAuthIdentifier(),
        )), 'Webhook suspendido.');
    }

    public function reactivate(string $subscriptionId, Request $request, CommandBus $commandBus): RedirectResponse
    {
        return $this->transition(fn (): mixed => $commandBus->dispatch(new ReactivateWebhookSubscriptionCommand(
            subscriptionId: $subscriptionId,
            actorId: (string) $request->user()?->getAuthIdentifier(),
        )), 'Webhook reactivado.');
    }

    public function rotateSecret(string $subscriptionId, Request $request, CommandBus $commandBus): RedirectResponse
    {
        try {
            $result = $commandBus->dispatch(new RotateWebhookSubscriptionSecretCommand(
                subscriptionId: $subscriptionId,
                actorId: (string) $request->user()?->getAuthIdentifier(),
            ));
            assert($result instanceof WebhookSubscriptionResponse);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Secreto rotado. Copialo ahora: no volverá a mostrarse.')
            ->with('webhook_secret', $result->secret);
    }

    public function retry(string $deliveryId, Request $request, CommandBus $commandBus): RedirectResponse
    {
        return $this->transition(fn (): mixed => $commandBus->dispatch(new RetryWebhookDeliveryCommand(
            deliveryId: $deliveryId,
            actorId: (string) $request->user()?->getAuthIdentifier(),
        )), 'Reintento solicitado.');
    }

    /** @param callable(): mixed $operation */
    private function transition(callable $operation, string $message): RedirectResponse
    {
        try {
            $operation();
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', $message);
    }
}
