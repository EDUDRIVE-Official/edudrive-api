<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetSystemSettingQuery implements Query
{
    public function __construct(
        public string $key,
    ) {}
}
