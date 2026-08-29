<?php

declare(strict_types=1);

namespace Modules\Mobile\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Mobile\Application\Commands\RegisterMobileDeviceCommand;
use Modules\Mobile\Application\Commands\RemoveMobileDeviceCommand;
use Modules\Mobile\Application\Queries\ListMobileDevicesQuery;
use Modules\Mobile\Application\Responses\MobileDeviceResponse;
use Modules\Mobile\Presentation\Http\Requests\RegisterMobileDeviceRequest;
use Symfony\Component\HttpFoundation\Response;

final class MobileDeviceController
{
    public function store(RegisterMobileDeviceRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RegisterMobileDeviceCommand(
            userId: (string) $request->user()?->getAuthIdentifier(),
            deviceId: (string) $data['device_id'],
            platform: (string) $data['platform'],
            pushToken: isset($data['push_token']) ? (string) $data['push_token'] : null,
            appVersion: (string) $data['app_version'],
        ));
        assert($result instanceof MobileDeviceResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function index(Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListMobileDevicesQuery(
            userId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (MobileDeviceResponse $device): array => $device->toArray(),
            $result,
        )]);
    }

    public function destroy(string $deviceId, Request $request, CommandBus $commandBus): JsonResponse
    {
        $commandBus->dispatch(new RemoveMobileDeviceCommand(
            userId: (string) $request->user()?->getAuthIdentifier(),
            deviceId: $deviceId,
        ));

        return response()->json(['data' => ['deleted' => true]]);
    }
}
