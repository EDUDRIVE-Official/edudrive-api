<?php

declare(strict_types=1);

namespace Modules\Webhook\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Webhook\Application\Commands\ReactivateWebhookSubscriptionCommand;
use Modules\Webhook\Application\Commands\RegisterWebhookSubscriptionCommand;
use Modules\Webhook\Application\Commands\RotateWebhookSubscriptionSecretCommand;
use Modules\Webhook\Application\Commands\SuspendWebhookSubscriptionCommand;
use Modules\Webhook\Application\Queries\GetWebhookSubscriptionQuery;
use Modules\Webhook\Application\Queries\ListWebhookSubscriptionsQuery;
use Modules\Webhook\Application\Responses\WebhookSubscriptionResponse;
use Modules\Webhook\Presentation\Http\Requests\RegisterWebhookSubscriptionRequest;
use Symfony\Component\HttpFoundation\Response;

final class WebhookSubscriptionController
{
    public function store(RegisterWebhookSubscriptionRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RegisterWebhookSubscriptionCommand(
            url: (string) $data['url'],
            events: $data['events'],
            actorId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof WebhookSubscriptionResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListWebhookSubscriptionsQuery);
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (WebhookSubscriptionResponse $subscription): array => $subscription->toArray(),
            $result,
        )]);
    }

    public function show(string $subscriptionId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetWebhookSubscriptionQuery(subscriptionId: $subscriptionId));
        assert($result instanceof WebhookSubscriptionResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function suspend(string $subscriptionId, Request $request, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new SuspendWebhookSubscriptionCommand(
            subscriptionId: $subscriptionId,
            actorId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof WebhookSubscriptionResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function reactivate(string $subscriptionId, Request $request, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new ReactivateWebhookSubscriptionCommand(
            subscriptionId: $subscriptionId,
            actorId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof WebhookSubscriptionResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function rotateSecret(string $subscriptionId, Request $request, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new RotateWebhookSubscriptionSecretCommand(
            subscriptionId: $subscriptionId,
            actorId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof WebhookSubscriptionResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
