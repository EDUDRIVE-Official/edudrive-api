<?php

declare(strict_types=1);

namespace Modules\Mobile\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Foundation\Domain\Exceptions\DomainException;
use Modules\Mobile\Application\Commands\RemoveMobileDeviceCommand;
use Modules\Mobile\Application\Queries\ListMobileDevicesQuery;
use Modules\Mobile\Application\Responses\MobileDeviceResponse;

final class MobileDeviceWebController
{
    public function index(Request $request, QueryBus $queryBus): View
    {
        $devices = $queryBus->ask(new ListMobileDevicesQuery(
            userId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert(is_array($devices));

        return view('mobile.devices', [
            'devices' => array_map(static fn (MobileDeviceResponse $device): array => $device->toArray(), $devices),
        ]);
    }

    public function destroy(string $deviceId, Request $request, CommandBus $commandBus): RedirectResponse
    {
        try {
            $commandBus->dispatch(new RemoveMobileDeviceCommand(
                userId: (string) $request->user()?->getAuthIdentifier(),
                deviceId: $deviceId,
            ));
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Dispositivo desvinculado correctamente.');
    }
}
