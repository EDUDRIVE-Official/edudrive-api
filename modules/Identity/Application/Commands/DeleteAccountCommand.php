<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Commands;

final readonly class DeleteAccountCommand
{
    public function __construct(
        public string $userId,
        public ?string $actorId = null,
    ) {}
}
