<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use DateTimeImmutable;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\RevokeGuardianRelationshipCommand;
use Modules\Identity\Application\Exceptions\GuardianRelationshipNotFound;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;

final readonly class RevokeGuardianRelationshipHandler
{
    public function __construct(
        private GuardianRelationshipRepository $relationships,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(RevokeGuardianRelationshipCommand $command): void
    {
        $relationship = $this->relationships->findById($command->relationshipId);

        if ($relationship === null) {
            throw new GuardianRelationshipNotFound;
        }

        $relationship->revoke(new DateTimeImmutable);

        $this->relationships->save($relationship);

        $this->auditLogger->log(new AuditEntry(
            action: 'identity.guardian_relationship_revoked',
            userId: $command->actorId,
            entity: 'GuardianRelationship',
            entityId: $relationship->id(),
        ));
    }
}
