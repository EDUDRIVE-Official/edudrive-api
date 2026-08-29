<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\AiGovernance\Application\Commands\ApproveAiModelCommand;
use Modules\AiGovernance\Application\Commands\DeprecateAiModelCommand;
use Modules\AiGovernance\Application\Commands\RegisterAiModelCommand;
use Modules\AiGovernance\Application\Commands\RetireAiModelCommand;
use Modules\AiGovernance\Application\Queries\GetAiModelQuery;
use Modules\AiGovernance\Application\Queries\ListAiModelsQuery;
use Modules\AiGovernance\Application\Responses\AiModelResponse;
use Modules\AiGovernance\Presentation\Http\Requests\RegisterAiModelRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class AiModelController
{
    public function store(RegisterAiModelRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RegisterAiModelCommand(
            name: (string) $data['name'],
            provider: (string) $data['provider'],
            version: (string) $data['version'],
            ownerId: isset($data['owner_id']) ? (string) $data['owner_id'] : null,
            useCase: isset($data['use_case']) ? (string) $data['use_case'] : null,
            knownRisks: isset($data['known_risks']) ? (string) $data['known_risks'] : null,
        ));
        assert($result instanceof AiModelResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListAiModelsQuery);
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (AiModelResponse $model): array => $model->toArray(),
            $result,
        )]);
    }

    public function show(string $modelId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetAiModelQuery(modelId: $modelId));
        assert($result instanceof AiModelResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function approve(string $modelId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new ApproveAiModelCommand(modelId: $modelId));
        assert($result instanceof AiModelResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function deprecate(string $modelId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new DeprecateAiModelCommand(modelId: $modelId));
        assert($result instanceof AiModelResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function retire(string $modelId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new RetireAiModelCommand(modelId: $modelId));
        assert($result instanceof AiModelResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
