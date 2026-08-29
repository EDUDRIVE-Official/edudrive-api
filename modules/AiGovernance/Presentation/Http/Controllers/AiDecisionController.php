<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AiGovernance\Application\Commands\ApproveAiDecisionCommand;
use Modules\AiGovernance\Application\Commands\RejectAiDecisionCommand;
use Modules\AiGovernance\Application\Queries\GetAiDecisionQuery;
use Modules\AiGovernance\Application\Queries\ListAiDecisionsQuery;
use Modules\AiGovernance\Application\Responses\AiDecisionResponse;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;

final class AiDecisionController
{
    public function index(string $aiSystemId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListAiDecisionsQuery(aiSystemId: $aiSystemId));
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (AiDecisionResponse $decision): array => $decision->toArray(),
            $result,
        )]);
    }

    public function show(string $decisionId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetAiDecisionQuery(decisionId: $decisionId));
        assert($result instanceof AiDecisionResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function approve(string $decisionId, Request $request, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new ApproveAiDecisionCommand(
            decisionId: $decisionId,
            reviewerUserId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof AiDecisionResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function reject(string $decisionId, Request $request, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new RejectAiDecisionCommand(
            decisionId: $decisionId,
            reviewerUserId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof AiDecisionResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
