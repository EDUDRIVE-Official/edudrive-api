<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\AiGovernance\Application\Commands\ReportAiIncidentCommand;
use Modules\AiGovernance\Application\Commands\ResolveAiIncidentCommand;
use Modules\AiGovernance\Application\Commands\StartAiIncidentInvestigationCommand;
use Modules\AiGovernance\Application\Queries\GetAiIncidentQuery;
use Modules\AiGovernance\Application\Queries\ListAiIncidentsQuery;
use Modules\AiGovernance\Application\Responses\AiIncidentResponse;
use Modules\AiGovernance\Presentation\Http\Requests\ReportAiIncidentRequest;
use Modules\AiGovernance\Presentation\Http\Requests\ResolveAiIncidentRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class AiIncidentController
{
    public function store(ReportAiIncidentRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new ReportAiIncidentCommand(
            aiSystemId: (string) $data['ai_system_id'],
            severity: (string) $data['severity'],
            description: (string) $data['description'],
        ));
        assert($result instanceof AiIncidentResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function index(string $aiSystemId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListAiIncidentsQuery(aiSystemId: $aiSystemId));
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (AiIncidentResponse $incident): array => $incident->toArray(),
            $result,
        )]);
    }

    public function show(string $incidentId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetAiIncidentQuery(incidentId: $incidentId));
        assert($result instanceof AiIncidentResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function startInvestigation(string $incidentId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new StartAiIncidentInvestigationCommand(incidentId: $incidentId));
        assert($result instanceof AiIncidentResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function resolve(string $incidentId, ResolveAiIncidentRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new ResolveAiIncidentCommand(
            incidentId: $incidentId,
            correctiveActions: (string) $data['corrective_actions'],
        ));
        assert($result instanceof AiIncidentResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
