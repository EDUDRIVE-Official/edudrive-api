<?php

declare(strict_types=1);

namespace Modules\Foundation\Application\Bus;

use Modules\Foundation\Application\Commands\Command;

interface CommandBus
{
    public function dispatch(Command $command): mixed;
}
