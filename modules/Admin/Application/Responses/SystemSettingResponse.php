<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Responses;

use DateTimeInterface;
use Modules\Admin\Domain\Aggregates\SystemSetting;

final readonly class SystemSettingResponse
{
    public function __construct(
        public string $key,
        public string $value,
        public string $changedAt,
    ) {}

    public static function fromSystemSetting(SystemSetting $setting): self
    {
        return new self(
            key: $setting->key()->value(),
            value: $setting->value(),
            changedAt: $setting->changedAt()->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'changed_at' => $this->changedAt,
        ];
    }
}
