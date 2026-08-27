<?php

declare(strict_types=1);

namespace Modules\Admin\Application\UseCases;

use Modules\Admin\Application\Queries\ListSystemSettingsQuery;
use Modules\Admin\Application\Responses\SystemSettingResponse;
use Modules\Admin\Domain\Aggregates\SystemSetting;
use Modules\Admin\Domain\Repositories\SystemSettingRepository;

final readonly class ListSystemSettingsHandler
{
    public function __construct(private SystemSettingRepository $settings) {}

    /** @return list<SystemSettingResponse> */
    public function handle(ListSystemSettingsQuery $query): array
    {
        return array_map(
            static fn (SystemSetting $setting): SystemSettingResponse => SystemSettingResponse::fromSystemSetting($setting),
            $this->settings->all(),
        );
    }
}
