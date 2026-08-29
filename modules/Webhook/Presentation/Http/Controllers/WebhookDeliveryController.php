<?php

declare(strict_types=1);

namespace Modules\Webhook\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Webhook\Application\Commands\RetryWebhookDeliveryCommand;
use Modules\Webhook\Application\Queries\ListWebhookDeliveriesQuery;
use Modules\Webhook\Application\Responses\WebhookDeliveryResponse;
use Modules\Webhook\Presentation\Http\Requests\ListWebhookDeliveriesRequest;

final class WebhookDeliveryController
{
    public function index(string $subscriptionId, ListWebhookDeliveriesRequest $request, QueryBus $queryBus): JsonResponse
    {
        $data = $request->validated();
        $result = $queryBus->ask(new ListWebhookDeliveriesQuery(
            subscriptionId: $subscriptionId,
            status: isset($data['status']) ? (string) $data['status'] : null,
        ));
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (WebhookDeliveryResponse $delivery): array => $delivery->toArray(),
            $result,
        )]);
    }

    public function retry(string $deliveryId, Request $request, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new RetryWebhookDeliveryCommand(
            deliveryId: $deliveryId,
            actorId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof WebhookDeliveryResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
