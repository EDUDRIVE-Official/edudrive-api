<?php

declare(strict_types=1);

namespace Modules\Simulation\Presentation\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Simulation\Application\Commands\SubmitDecisionPointsCommand;
use Modules\Simulation\Application\Queries\GetDecisionEngineResultQuery;
use Modules\Simulation\Application\Responses\DecisionEngineResultResponse;
use Modules\Simulation\Application\Responses\DecisionPointsBatchResponse;
use Modules\Simulation\Presentation\Http\Requests\SubmitDecisionPointsRequest;
use Symfony\Component\HttpFoundation\Response;

final class DecisionEngineController
{
    public function store(string $sessionId, SubmitDecisionPointsRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $simulatorId = (string) $request->attributes->get('authenticated_simulator_id');

        $result = $commandBus->dispatch(new SubmitDecisionPointsCommand(
            sessionId: $sessionId,
            simulatorId: $simulatorId,
            decisions: $data['decisions'] ?? [],
        ));
        assert($result instanceof DecisionPointsBatchResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function show(
        string $sessionId,
        Request $request,
        QueryBus $queryBus,
        PermissionChecker $permissionChecker,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetDecisionEngineResultQuery(
            sessionId: $sessionId,
            userId: (string) $user->getAuthIdentifier(),
            canViewOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ViewSimulationSessions),
        ));
        assert($result instanceof DecisionEngineResultResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    private static function authenticatedUser(Request $request): Authenticatable
    {
        $user = $request->user();
        assert($user instanceof Authenticatable);

        return $user;
    }
}
