<?php

declare(strict_types=1);

namespace Modules\Admin\Application\UseCases;

use DateTimeImmutable;
use Modules\Admin\Application\Commands\SetSystemSettingCommand;
use Modules\Admin\Application\Responses\SystemSettingResponse;
use Modules\Admin\Domain\Aggregates\SystemSetting;
use Modules\Admin\Domain\Repositories\SystemSettingRepository;
use Modules\Admin\Domain\ValueObjects\SystemSettingKey;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;

final readonly class SetSystemSettingHandler
{
    public function __construct(
        private SystemSettingRepository $settings,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(SetSystemSettingCommand $command): SystemSettingResponse
    {
        $key = SystemSettingKey::fromString($command->key);
        $now = new DateTimeImmutable('now');

        $setting = $this->settings->findByKey($key);
        $oldValue = $setting?->value();

        if ($setting === null) {
            $setting = SystemSetting::set($key, $command->value, $now);
        } else {
            $setting->updateValue($command->value, $now);
        }

        $this->settings->save($setting);

        $this->auditLogger->log(
            new AuditEntry(
                action: 'admin.system_setting_changed',
                userId: $command->actorId,
                entity: 'SystemSetting',
                entityId: $command->key,
                metadata: [
                    'old_value' => $oldValue,
                    'new_value' => $command->value,
                ],
            ),
        );

        return SystemSettingResponse::fromSystemSetting($setting);
    }
}
