<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class AssignRoleCommand implements Command
{
    public function __construct(
        public string $userId,
        public string $role,
        public ?string $organizationId,
        public ?string $actorId = null,
    ) {}
}
