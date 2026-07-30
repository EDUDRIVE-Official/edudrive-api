<?php

declare(strict_types=1);

namespace Modules\Foundation\Application\Bus;

use Modules\Foundation\Application\Queries\Query;

interface QueryBus
{
    public function ask(Query $query): mixed;
}
