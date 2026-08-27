<?php

declare(strict_types=1);

namespace Modules\Admin\Domain\Repositories;

use Modules\Admin\Domain\Aggregates\SystemSetting;
use Modules\Admin\Domain\ValueObjects\SystemSettingKey;

interface SystemSettingRepository
{
    public function save(SystemSetting $setting): void;

    public function findByKey(SystemSettingKey $key): ?SystemSetting;

    /** @return list<SystemSetting> */
    public function all(): array;
}
