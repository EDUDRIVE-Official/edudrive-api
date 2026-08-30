<?php

declare(strict_types=1);

namespace Modules\Legal\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class RevokeConsentCommand implements Command
{
    public function __construct(
        public string $userId,
        public string $policyKey,
    ) {}
}
