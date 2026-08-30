<?php

declare(strict_types=1);

use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\RevokeGuardianRelationshipCommand;
use Modules\Identity\Application\Exceptions\GuardianRelationshipNotFound;
use Modules\Identity\Application\UseCases\RevokeGuardianRelationshipHandler;
use Modules\Identity\Domain\Entities\GuardianRelationship;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;

final class InMemoryGuardianRelationshipRepositoryForRevoke implements GuardianRelationshipRepository
{
    /** @var array<string, GuardianRelationship> */
    public array $items = [];

    public function save(GuardianRelationship $relationship): void
    {
        $this->items[$relationship->id()] = $relationship;
    }

    public function findById(string $id): ?GuardianRelationship
    {
        return $this->items[$id] ?? null;
    }

    public function findActiveByGuardianAndMinor(string $guardianUserId, string $minorUserId): ?GuardianRelationship
    {
        return null;
    }

    /** @return list<GuardianRelationship> */
    public function findActiveByGuardian(string $guardianUserId): array
    {
        return [];
    }
}

final class SpyAuditLoggerForRevokeGuardian implements AuditLogger
{
    /** @var list<AuditEntry> */
    public array $entries = [];

    public function log(AuditEntry $entry): void
    {
        $this->entries[] = $entry;
    }
}

it('revoca una relacion tutor-menor y audita la operacion', function (): void {
    $relationships = new InMemoryGuardianRelationshipRepositoryForRevoke;
    $relationships->save(GuardianRelationship::create(
        id: 'relationship-1',
        guardianUserId: 'guardian-1',
        minorUserId: 'minor-1',
    ));
    $audit = new SpyAuditLoggerForRevokeGuardian;

    $handler = new RevokeGuardianRelationshipHandler($relationships, $audit);
    $handler->handle(new RevokeGuardianRelationshipCommand(relationshipId: 'relationship-1', actorId: 'admin-1'));

    expect($relationships->items['relationship-1']->isActive())->toBeFalse()
        ->and($audit->entries)->toHaveCount(1)
        ->and($audit->entries[0]->action)->toBe('identity.guardian_relationship_revoked')
        ->and($audit->entries[0]->userId)->toBe('admin-1');
});

it('rechaza revocar una relacion inexistente', function (): void {
    $handler = new RevokeGuardianRelationshipHandler(
        new InMemoryGuardianRelationshipRepositoryForRevoke,
        new SpyAuditLoggerForRevokeGuardian,
    );

    $handler->handle(new RevokeGuardianRelationshipCommand(relationshipId: 'no-existe', actorId: 'admin-1'));
})->throws(GuardianRelationshipNotFound::class);
