<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Commands;

final readonly class CreateGuardianRelationshipCommand
{
    public function __construct(
        public string $guardianUserId,
        public string $minorUserId,
        public string $actorId,
    ) {}
}
