<?php

declare(strict_types=1);

namespace Modules\Admin\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Admin\Domain\ValueObjects\SystemSettingKey;

final class SystemSetting
{
    private function __construct(
        private SystemSettingKey $key,
        private string $value,
        private DateTimeImmutable $changedAt,
    ) {}

    public static function set(
        SystemSettingKey $key,
        string $value,
        ?DateTimeImmutable $changedAt = null,
    ): self {
        return new self($key, $value, $changedAt ?? new DateTimeImmutable('now'));
    }

    public static function restore(
        SystemSettingKey $key,
        string $value,
        DateTimeImmutable $changedAt,
    ): self {
        return new self($key, $value, $changedAt);
    }

    public function updateValue(string $value, DateTimeImmutable $changedAt): void
    {
        $this->value = $value;
        $this->changedAt = $changedAt;
    }

    public function key(): SystemSettingKey
    {
        return $this->key;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function changedAt(): DateTimeImmutable
    {
        return $this->changedAt;
    }
}
