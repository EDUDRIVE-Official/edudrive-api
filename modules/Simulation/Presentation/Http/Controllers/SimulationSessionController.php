<?php

declare(strict_types=1);

namespace Modules\Simulation\Presentation\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Simulation\Application\Commands\CancelSimulationSessionCommand;
use Modules\Simulation\Application\Commands\CompleteSimulationSessionCommand;
use Modules\Simulation\Application\Commands\ScheduleSimulationSessionCommand;
use Modules\Simulation\Application\Commands\StartSimulationSessionCommand;
use Modules\Simulation\Application\Queries\GetMySimulationSessionsQuery;
use Modules\Simulation\Application\Queries\GetPracticalResultQuery;
use Modules\Simulation\Application\Queries\GetSimulationSessionQuery;
use Modules\Simulation\Application\Queries\ListSimulationSessionsQuery;
use Modules\Simulation\Application\Responses\PracticalResultResponse;
use Modules\Simulation\Application\Responses\SimulationSessionResponse;
use Modules\Simulation\Presentation\Http\Requests\CancelSimulationSessionRequest;
use Modules\Simulation\Presentation\Http\Requests\ScheduleSimulationSessionRequest;
use Symfony\Component\HttpFoundation\Response;

final class SimulationSessionController
{
    public function store(ScheduleSimulationSessionRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $user = self::authenticatedUser($request);
        $result = $commandBus->dispatch(new ScheduleSimulationSessionCommand(
            userId: (string) $user->getAuthIdentifier(),
            simulatorId: (string) $data['simulator_id'],
            vehicleType: (string) $data['vehicle_type'],
            scenario: (string) $data['scenario'],
            scheduledAt: new DateTimeImmutable((string) $data['scheduled_at']),
            plannedDurationMinutes: (int) $data['planned_duration_minutes'],
        ));
        assert($result instanceof SimulationSessionResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function me(Request $request, QueryBus $queryBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetMySimulationSessionsQuery(userId: (string) $user->getAuthIdentifier()));
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (SimulationSessionResponse $session): array => $session->toArray(),
            $result,
        )]);
    }

    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListSimulationSessionsQuery);
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (SimulationSessionResponse $session): array => $session->toArray(),
            $result,
        )]);
    }

    public function show(
        string $sessionId,
        Request $request,
        QueryBus $queryBus,
        PermissionChecker $permissionChecker,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetSimulationSessionQuery(
            sessionId: $sessionId,
            userId: (string) $user->getAuthIdentifier(),
            canViewOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ViewSimulationSessions),
        ));
        assert($result instanceof SimulationSessionResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function start(
        string $sessionId,
        Request $request,
        CommandBus $commandBus,
        PermissionChecker $permissionChecker,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $result = $commandBus->dispatch(new StartSimulationSessionCommand(
            sessionId: $sessionId,
            userId: (string) $user->getAuthIdentifier(),
            canManageOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ManageSimulationSessions),
        ));
        assert($result instanceof SimulationSessionResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function complete(
        string $sessionId,
        Request $request,
        CommandBus $commandBus,
        PermissionChecker $permissionChecker,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $result = $commandBus->dispatch(new CompleteSimulationSessionCommand(
            sessionId: $sessionId,
            userId: (string) $user->getAuthIdentifier(),
            canManageOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ManageSimulationSessions),
        ));
        assert($result instanceof SimulationSessionResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function cancel(
        string $sessionId,
        CancelSimulationSessionRequest $request,
        CommandBus $commandBus,
        PermissionChecker $permissionChecker,
    ): JsonResponse {
        $data = $request->validated();
        $user = self::authenticatedUser($request);
        $result = $commandBus->dispatch(new CancelSimulationSessionCommand(
            sessionId: $sessionId,
            userId: (string) $user->getAuthIdentifier(),
            canManageOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ManageSimulationSessions),
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
        ));
        assert($result instanceof SimulationSessionResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function result(
        string $sessionId,
        Request $request,
        QueryBus $queryBus,
        PermissionChecker $permissionChecker,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetPracticalResultQuery(
            sessionId: $sessionId,
            userId: (string) $user->getAuthIdentifier(),
            canViewOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ViewSimulationSessions),
        ));
        assert($result instanceof PracticalResultResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    private static function authenticatedUser(Request $request): Authenticatable
    {
        $user = $request->user();
        assert($user instanceof Authenticatable);

        return $user;
    }
}
