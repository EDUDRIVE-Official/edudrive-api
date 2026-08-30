<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\CreateGuardianRelationshipCommand;
use Modules\Identity\Application\Exceptions\GuardianRelationshipAlreadyExists;
use Modules\Identity\Application\Exceptions\GuardianRelationshipRequiresMinor;
use Modules\Identity\Application\Responses\GuardianRelationshipResponse;
use Modules\Identity\Domain\Entities\GuardianRelationship;
use Modules\Identity\Domain\Exceptions\UserNotFound;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;
use Modules\Identity\Domain\Repositories\UserRepository;

final readonly class CreateGuardianRelationshipHandler
{
    public function __construct(
        private GuardianRelationshipRepository $relationships,
        private UserRepository $users,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(CreateGuardianRelationshipCommand $command): GuardianRelationshipResponse
    {
        if ($this->users->findById($command->guardianUserId) === null) {
            throw new UserNotFound;
        }

        $minor = $this->users->findById($command->minorUserId);

        if ($minor === null) {
            throw new UserNotFound;
        }

        if (! $minor->isMinor()) {
            throw new GuardianRelationshipRequiresMinor;
        }

        if ($this->relationships->findActiveByGuardianAndMinor($command->guardianUserId, $command->minorUserId) !== null) {
            throw new GuardianRelationshipAlreadyExists;
        }

        $relationship = GuardianRelationship::create(
            id: (string) Str::uuid(),
            guardianUserId: $command->guardianUserId,
            minorUserId: $command->minorUserId,
        );

        $this->relationships->save($relationship);

        $this->auditLogger->log(new AuditEntry(
            action: 'identity.guardian_relationship_created',
            userId: $command->actorId,
            entity: 'GuardianRelationship',
            entityId: $relationship->id(),
            metadata: [
                'guardian_user_id' => $command->guardianUserId,
                'minor_user_id' => $command->minorUserId,
            ],
        ));

        return GuardianRelationshipResponse::fromRelationship($relationship);
    }
}
