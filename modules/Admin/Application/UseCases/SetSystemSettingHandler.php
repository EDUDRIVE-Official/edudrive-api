<?php

declare(strict_types=1);

namespace Modules\Admin\Application\UseCases;

use DateTimeImmutable;
use Modules\Admin\Application\Commands\SetSystemSettingCommand;
use Modules\Admin\Application\Responses\SystemSettingResponse;
use Modules\Admin\Domain\Aggregates\SystemSetting;
use Modules\Admin\Domain\Repositories\SystemSettingRepository;
use Modules\Admin\Domain\ValueObjects\SystemSettingKey;

final readonly class SetSystemSettingHandler
{
    public function __construct(private SystemSettingRepository $settings) {}

    public function handle(SetSystemSettingCommand $command): SystemSettingResponse
    {
        $key = SystemSettingKey::fromString($command->key);
        $now = new DateTimeImmutable('now');

        $setting = $this->settings->findByKey($key);

        if ($setting === null) {
            $setting = SystemSetting::set($key, $command->value, $now);
        } else {
            $setting->updateValue($command->value, $now);
        }

        $this->settings->save($setting);

        return SystemSettingResponse::fromSystemSetting($setting);
    }
}
