<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\AiGovernance\Application\Commands\ApproveAiProviderEvaluationCommand;
use Modules\AiGovernance\Application\Commands\RegisterAiProviderEvaluationCommand;
use Modules\AiGovernance\Application\Commands\RejectAiProviderEvaluationCommand;
use Modules\AiGovernance\Application\Commands\RequireAiProviderReevaluationCommand;
use Modules\AiGovernance\Application\Queries\GetAiProviderEvaluationQuery;
use Modules\AiGovernance\Application\Queries\ListAiProviderEvaluationsQuery;
use Modules\AiGovernance\Application\Responses\AiProviderEvaluationResponse;
use Modules\AiGovernance\Presentation\Http\Requests\ApproveAiProviderEvaluationRequest;
use Modules\AiGovernance\Presentation\Http\Requests\RegisterAiProviderEvaluationRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class AiProviderEvaluationController
{
    public function store(RegisterAiProviderEvaluationRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RegisterAiProviderEvaluationCommand(
            providerName: (string) $data['provider_name'],
            dataLocation: (string) $data['data_location'],
            retentionPolicy: (string) $data['retention_policy'],
            securityReviewNotes: isset($data['security_review_notes']) ? (string) $data['security_review_notes'] : null,
        ));
        assert($result instanceof AiProviderEvaluationResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListAiProviderEvaluationsQuery);
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (AiProviderEvaluationResponse $evaluation): array => $evaluation->toArray(),
            $result,
        )]);
    }

    public function show(string $providerEvaluationId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetAiProviderEvaluationQuery(providerEvaluationId: $providerEvaluationId));
        assert($result instanceof AiProviderEvaluationResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function approve(string $providerEvaluationId, ApproveAiProviderEvaluationRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new ApproveAiProviderEvaluationCommand(
            providerEvaluationId: $providerEvaluationId,
            nextReviewDueAt: isset($data['next_review_due_at']) ? (string) $data['next_review_due_at'] : null,
        ));
        assert($result instanceof AiProviderEvaluationResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function reject(string $providerEvaluationId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new RejectAiProviderEvaluationCommand(providerEvaluationId: $providerEvaluationId));
        assert($result instanceof AiProviderEvaluationResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function requireReevaluation(string $providerEvaluationId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new RequireAiProviderReevaluationCommand(providerEvaluationId: $providerEvaluationId));
        assert($result instanceof AiProviderEvaluationResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
