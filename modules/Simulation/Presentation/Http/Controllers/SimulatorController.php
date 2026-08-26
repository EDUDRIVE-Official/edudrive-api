<?php

declare(strict_types=1);

namespace Modules\Simulation\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Simulation\Application\Commands\ReactivateSimulatorCommand;
use Modules\Simulation\Application\Commands\RegisterSimulatorCommand;
use Modules\Simulation\Application\Commands\RetireSimulatorCommand;
use Modules\Simulation\Application\Commands\RotateSimulatorIntegrationKeyCommand;
use Modules\Simulation\Application\Commands\SuspendSimulatorCommand;
use Modules\Simulation\Application\Queries\GetSimulatorQuery;
use Modules\Simulation\Application\Queries\ListSimulatorsQuery;
use Modules\Simulation\Application\Responses\SimulatorResponse;
use Modules\Simulation\Presentation\Http\Requests\RegisterSimulatorRequest;
use Modules\Simulation\Presentation\Http\Requests\RetireSimulatorRequest;
use Modules\Simulation\Presentation\Http\Requests\SuspendSimulatorRequest;
use Symfony\Component\HttpFoundation\Response;

final class SimulatorController
{
    public function store(RegisterSimulatorRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RegisterSimulatorCommand(
            deviceIdentifier: (string) $data['device_identifier'],
            softwareVersion: (string) $data['software_version'],
            location: isset($data['location']) ? (string) $data['location'] : null,
        ));
        assert($result instanceof SimulatorResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListSimulatorsQuery);
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (SimulatorResponse $simulator): array => $simulator->toArray(),
            $result,
        )]);
    }

    public function show(string $simulatorId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetSimulatorQuery(simulatorId: $simulatorId));
        assert($result instanceof SimulatorResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function suspend(string $simulatorId, SuspendSimulatorRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new SuspendSimulatorCommand(
            simulatorId: $simulatorId,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
        ));
        assert($result instanceof SimulatorResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function reactivate(string $simulatorId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new ReactivateSimulatorCommand(simulatorId: $simulatorId));
        assert($result instanceof SimulatorResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function retire(string $simulatorId, RetireSimulatorRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RetireSimulatorCommand(
            simulatorId: $simulatorId,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
        ));
        assert($result instanceof SimulatorResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function rotateKey(string $simulatorId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new RotateSimulatorIntegrationKeyCommand(simulatorId: $simulatorId));
        assert($result instanceof SimulatorResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
