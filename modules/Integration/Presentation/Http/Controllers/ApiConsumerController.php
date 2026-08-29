<?php

declare(strict_types=1);

namespace Modules\Integration\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Integration\Application\Commands\ReactivateApiConsumerCommand;
use Modules\Integration\Application\Commands\RegisterApiConsumerCommand;
use Modules\Integration\Application\Commands\RevokeApiConsumerCommand;
use Modules\Integration\Application\Commands\RotateApiConsumerIntegrationKeyCommand;
use Modules\Integration\Application\Commands\SuspendApiConsumerCommand;
use Modules\Integration\Application\Queries\GetApiConsumerQuery;
use Modules\Integration\Application\Queries\ListApiConsumersQuery;
use Modules\Integration\Application\Responses\ApiConsumerResponse;
use Modules\Integration\Presentation\Http\Requests\RegisterApiConsumerRequest;
use Modules\Integration\Presentation\Http\Requests\RevokeApiConsumerRequest;
use Modules\Integration\Presentation\Http\Requests\SuspendApiConsumerRequest;
use Symfony\Component\HttpFoundation\Response;

final class ApiConsumerController
{
    public function store(RegisterApiConsumerRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RegisterApiConsumerCommand(
            name: (string) $data['name'],
            scopes: $data['scopes'],
            expiresAt: isset($data['expires_at']) ? (string) $data['expires_at'] : null,
            actorId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof ApiConsumerResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListApiConsumersQuery);
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (ApiConsumerResponse $consumer): array => $consumer->toArray(),
            $result,
        )]);
    }

    public function show(string $consumerId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetApiConsumerQuery(consumerId: $consumerId));
        assert($result instanceof ApiConsumerResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function suspend(string $consumerId, SuspendApiConsumerRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new SuspendApiConsumerCommand(
            consumerId: $consumerId,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
            actorId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof ApiConsumerResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function reactivate(string $consumerId, Request $request, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new ReactivateApiConsumerCommand(
            consumerId: $consumerId,
            actorId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof ApiConsumerResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function revoke(string $consumerId, RevokeApiConsumerRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RevokeApiConsumerCommand(
            consumerId: $consumerId,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
            actorId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof ApiConsumerResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function rotateKey(string $consumerId, Request $request, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new RotateApiConsumerIntegrationKeyCommand(
            consumerId: $consumerId,
            actorId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof ApiConsumerResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
