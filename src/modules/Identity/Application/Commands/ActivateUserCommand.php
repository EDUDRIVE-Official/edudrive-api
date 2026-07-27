<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Commands;

final readonly class ActivateUserCommand
{
    public function __construct(
        public string $userId,
    ) {}
}
