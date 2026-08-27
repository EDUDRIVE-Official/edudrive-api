<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class UpdateOrganizationCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $name,
    ) {}
}
