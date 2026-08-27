<?php

declare(strict_types=1);

namespace Modules\FileStorage\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class SetFileScanStatusCommand implements Command
{
    public function __construct(
        public string $fileId,
        public string $scanStatus,
    ) {}
}
