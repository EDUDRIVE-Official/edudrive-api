<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Application\Commands\SetSystemSettingCommand;
use Modules\Admin\Application\Queries\GetSystemSettingQuery;
use Modules\Admin\Application\Queries\ListSystemSettingsQuery;
use Modules\Admin\Application\Responses\SystemSettingResponse;
use Modules\Admin\Presentation\Http\Requests\SetSystemSettingRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;

final class SystemSettingController
{
    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListSystemSettingsQuery);
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (SystemSettingResponse $setting): array => $setting->toArray(),
            $result,
        )]);
    }

    public function show(string $key, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetSystemSettingQuery(key: $key));
        assert($result instanceof SystemSettingResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function update(string $key, SetSystemSettingRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new SetSystemSettingCommand(
            key: $key,
            value: (string) $data['value'],
        ));
        assert($result instanceof SystemSettingResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
