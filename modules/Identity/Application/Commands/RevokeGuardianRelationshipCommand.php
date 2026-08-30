<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Commands;

final readonly class RevokeGuardianRelationshipCommand
{
    public function __construct(
        public string $relationshipId,
        public string $actorId,
    ) {}
}
