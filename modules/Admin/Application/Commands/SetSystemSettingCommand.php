<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class SetSystemSettingCommand implements Command
{
    public function __construct(
        public string $key,
        public string $value,
        public string $actorId,
    ) {}
}
