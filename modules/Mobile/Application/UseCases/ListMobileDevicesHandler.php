<?php

declare(strict_types=1);

namespace Modules\Mobile\Application\UseCases;

use Modules\Mobile\Application\Queries\ListMobileDevicesQuery;
use Modules\Mobile\Application\Responses\MobileDeviceResponse;
use Modules\Mobile\Domain\Aggregates\MobileDevice;
use Modules\Mobile\Domain\Repositories\MobileDeviceRepository;

final readonly class ListMobileDevicesHandler
{
    public function __construct(private MobileDeviceRepository $devices) {}

    /** @return list<MobileDeviceResponse> */
    public function handle(ListMobileDevicesQuery $query): array
    {
        return array_map(
            static fn (MobileDevice $device): MobileDeviceResponse => MobileDeviceResponse::fromDevice($device),
            $this->devices->findByUser($query->userId),
        );
    }
}
