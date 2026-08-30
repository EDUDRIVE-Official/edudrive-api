<?php

declare(strict_types=1);

use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\CreateGuardianRelationshipCommand;
use Modules\Identity\Application\Exceptions\GuardianRelationshipAlreadyExists;
use Modules\Identity\Application\Exceptions\GuardianRelationshipRequiresMinor;
use Modules\Identity\Application\UseCases\CreateGuardianRelationshipHandler;
use Modules\Identity\Domain\Entities\GuardianRelationship;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Exceptions\UserNotFound;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

final class InMemoryUserRepositoryForCreateGuardian implements UserRepository
{
    /** @var array<string, User> */
    public array $items = [];

    public function save(User $user): void
    {
        $this->items[$user->id()] = $user;
    }

    public function findById(string $id): ?User
    {
        return $this->items[$id] ?? null;
    }

    public function findByEmail(Email $email): ?User
    {
        return null;
    }

    public function existsByEmail(Email $email): bool
    {
        return false;
    }

    public function delete(string $id): void
    {
        unset($this->items[$id]);
    }

    /** @return list<User> */
    public function all(): array
    {
        return array_values($this->items);
    }

    /** @return list<User> */
    public function findInactiveBefore(DateTimeImmutable $threshold): array
    {
        return [];
    }
}

final class InMemoryGuardianRelationshipRepositoryForCreate implements GuardianRelationshipRepository
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
        foreach ($this->items as $relationship) {
            if ($relationship->guardianUserId() === $guardianUserId
                && $relationship->minorUserId() === $minorUserId
                && $relationship->isActive()) {
                return $relationship;
            }
        }

        return null;
    }

    /** @return list<GuardianRelationship> */
    public function findActiveByGuardian(string $guardianUserId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (GuardianRelationship $relationship): bool => $relationship->guardianUserId() === $guardianUserId && $relationship->isActive(),
        ));
    }
}

final class SpyAuditLoggerForCreateGuardian implements AuditLogger
{
    /** @var list<AuditEntry> */
    public array $entries = [];

    public function log(AuditEntry $entry): void
    {
        $this->entries[] = $entry;
    }
}

function createGuardianAdult(string $id): User
{
    return User::register(
        id: $id,
        name: 'Tutor de prueba',
        email: Email::fromString($id.'@edudrive.cr'),
        passwordHash: 'hashed-password',
        dateOfBirth: new DateTimeImmutable('1990-01-01'),
    );
}

function createGuardianMinor(string $id): User
{
    return User::register(
        id: $id,
        name: 'Menor de prueba',
        email: Email::fromString($id.'@edudrive.cr'),
        passwordHash: 'hashed-password',
        dateOfBirth: new DateTimeImmutable('2015-01-01'),
    );
}

it('crea una relacion tutor-menor y audita la operacion', function (): void {
    $users = new InMemoryUserRepositoryForCreateGuardian;
    $users->save(createGuardianAdult('guardian-1'));
    $users->save(createGuardianMinor('minor-1'));

    $relationships = new InMemoryGuardianRelationshipRepositoryForCreate;
    $audit = new SpyAuditLoggerForCreateGuardian;

    $handler = new CreateGuardianRelationshipHandler($relationships, $users, $audit);
    $response = $handler->handle(new CreateGuardianRelationshipCommand(
        guardianUserId: 'guardian-1',
        minorUserId: 'minor-1',
        actorId: 'admin-1',
    ));

    expect($response->guardianUserId)->toBe('guardian-1')
        ->and($response->minorUserId)->toBe('minor-1')
        ->and($response->isActive)->toBeTrue()
        ->and($relationships->items)->toHaveCount(1)
        ->and($audit->entries)->toHaveCount(1)
        ->and($audit->entries[0]->action)->toBe('identity.guardian_relationship_created')
        ->and($audit->entries[0]->userId)->toBe('admin-1');
});

it('rechaza vincular un tutor a un usuario que no es menor de edad', function (): void {
    $users = new InMemoryUserRepositoryForCreateGuardian;
    $users->save(createGuardianAdult('guardian-1'));
    $users->save(createGuardianAdult('adult-1'));

    $handler = new CreateGuardianRelationshipHandler(
        new InMemoryGuardianRelationshipRepositoryForCreate,
        $users,
        new SpyAuditLoggerForCreateGuardian,
    );

    $handler->handle(new CreateGuardianRelationshipCommand(
        guardianUserId: 'guardian-1',
        minorUserId: 'adult-1',
        actorId: 'admin-1',
    ));
})->throws(GuardianRelationshipRequiresMinor::class);

it('rechaza duplicar una relacion activa ya existente', function (): void {
    $users = new InMemoryUserRepositoryForCreateGuardian;
    $users->save(createGuardianAdult('guardian-1'));
    $users->save(createGuardianMinor('minor-1'));

    $relationships = new InMemoryGuardianRelationshipRepositoryForCreate;
    $relationships->save(GuardianRelationship::create(
        id: 'existing-1',
        guardianUserId: 'guardian-1',
        minorUserId: 'minor-1',
    ));

    $handler = new CreateGuardianRelationshipHandler($relationships, $users, new SpyAuditLoggerForCreateGuardian);

    $handler->handle(new CreateGuardianRelationshipCommand(
        guardianUserId: 'guardian-1',
        minorUserId: 'minor-1',
        actorId: 'admin-1',
    ));
})->throws(GuardianRelationshipAlreadyExists::class);

it('rechaza vincular con un tutor o menor inexistente', function (): void {
    $handler = new CreateGuardianRelationshipHandler(
        new InMemoryGuardianRelationshipRepositoryForCreate,
        new InMemoryUserRepositoryForCreateGuardian,
        new SpyAuditLoggerForCreateGuardian,
    );

    $handler->handle(new CreateGuardianRelationshipCommand(
        guardianUserId: 'no-existe',
        minorUserId: 'tampoco-existe',
        actorId: 'admin-1',
    ));
})->throws(UserNotFound::class);
