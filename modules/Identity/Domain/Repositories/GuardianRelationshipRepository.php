<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Repositories;

use Modules\Identity\Domain\Entities\GuardianRelationship;

interface GuardianRelationshipRepository
{
    public function save(GuardianRelationship $relationship): void;

    public function findById(string $id): ?GuardianRelationship;

    public function findActiveByGuardianAndMinor(string $guardianUserId, string $minorUserId): ?GuardianRelationship;

    /** @return list<GuardianRelationship> */
    public function findActiveByGuardian(string $guardianUserId): array;
}
