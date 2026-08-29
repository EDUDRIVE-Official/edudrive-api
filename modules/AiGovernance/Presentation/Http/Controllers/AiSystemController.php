<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\AiGovernance\Application\Commands\ApproveAiSystemByCommitteeCommand;
use Modules\AiGovernance\Application\Commands\GrantAiSystemExtraordinaryApprovalCommand;
use Modules\AiGovernance\Application\Commands\PromoteAiSystemCommand;
use Modules\AiGovernance\Application\Commands\RegisterAiSystemCommand;
use Modules\AiGovernance\Application\Queries\GetAiSystemQuery;
use Modules\AiGovernance\Application\Queries\ListAiSystemsQuery;
use Modules\AiGovernance\Application\Responses\AiSystemResponse;
use Modules\AiGovernance\Presentation\Http\Requests\PromoteAiSystemRequest;
use Modules\AiGovernance\Presentation\Http\Requests\RegisterAiSystemRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class AiSystemController
{
    public function store(RegisterAiSystemRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RegisterAiSystemCommand(
            name: (string) $data['name'],
            purpose: (string) $data['purpose'],
            functionalOwnerId: (string) $data['functional_owner_id'],
            technicalOwnerId: isset($data['technical_owner_id']) ? (string) $data['technical_owner_id'] : null,
            riskLevel: (string) $data['risk_level'],
            supervisionLevel: (int) $data['supervision_level'],
            dataCategories: $data['data_categories'],
            providerEvaluationId: isset($data['provider_evaluation_id']) ? (string) $data['provider_evaluation_id'] : null,
        ));
        assert($result instanceof AiSystemResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListAiSystemsQuery);
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (AiSystemResponse $system): array => $system->toArray(),
            $result,
        )]);
    }

    public function show(string $aiSystemId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetAiSystemQuery(aiSystemId: $aiSystemId));
        assert($result instanceof AiSystemResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function promote(string $aiSystemId, PromoteAiSystemRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new PromoteAiSystemCommand(
            aiSystemId: $aiSystemId,
            status: (string) $data['status'],
        ));
        assert($result instanceof AiSystemResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function grantExtraordinaryApproval(string $aiSystemId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new GrantAiSystemExtraordinaryApprovalCommand(aiSystemId: $aiSystemId));
        assert($result instanceof AiSystemResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function approveByCommittee(string $aiSystemId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new ApproveAiSystemByCommitteeCommand(aiSystemId: $aiSystemId));
        assert($result instanceof AiSystemResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
