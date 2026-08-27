<?php

declare(strict_types=1);

namespace Modules\Admin\Application\UseCases;

use Modules\Admin\Application\Exceptions\SystemSettingNotFound;
use Modules\Admin\Application\Queries\GetSystemSettingQuery;
use Modules\Admin\Application\Responses\SystemSettingResponse;
use Modules\Admin\Domain\Repositories\SystemSettingRepository;
use Modules\Admin\Domain\ValueObjects\SystemSettingKey;

final readonly class GetSystemSettingHandler
{
    public function __construct(private SystemSettingRepository $settings) {}

    public function handle(GetSystemSettingQuery $query): SystemSettingResponse
    {
        $setting = $this->settings->findByKey(SystemSettingKey::fromString($query->key));

        if ($setting === null) {
            throw SystemSettingNotFound::withKey($query->key);
        }

        return SystemSettingResponse::fromSystemSetting($setting);
    }
}
