<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class RegisterApiConsumerCommand implements Command
{
    /** @param list<string> $scopes */
    public function __construct(
        public string $name,
        public array $scopes,
        public ?string $expiresAt,
        public string $actorId,
    ) {}
}
