<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetMyNotificationsQuery implements Query
{
    public function __construct(
        public string $userId,
    ) {}
}
