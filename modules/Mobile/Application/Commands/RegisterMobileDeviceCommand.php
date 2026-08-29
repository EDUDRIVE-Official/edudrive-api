<?php

declare(strict_types=1);

namespace Modules\Mobile\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class RegisterMobileDeviceCommand implements Command
{
    public function __construct(
        public string $userId,
        public string $deviceId,
        public string $platform,
        public ?string $pushToken,
        public string $appVersion,
    ) {}
}
