<?php

declare(strict_types=1);

use Modules\Identity\Application\Queries\ListMyLinkedMinorsQuery;
use Modules\Identity\Application\UseCases\ListMyLinkedMinorsHandler;
use Modules\Identity\Domain\Entities\GuardianRelationship;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

final class InMemoryGuardianRelationshipRepositoryForList implements GuardianRelationshipRepository
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
        return array_values(array_filter(
            $this->items,
            static fn (GuardianRelationship $relationship): bool => $relationship->guardianUserId() === $guardianUserId && $relationship->isActive(),
        ));
    }
}

final class InMemoryUserRepositoryForList implements UserRepository
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

function listLinkedMinorTestUser(string $id, string $name): User
{
    return User::register(
        id: $id,
        name: $name,
        email: Email::fromString($id.'@edudrive.cr'),
        passwordHash: 'hashed-password',
        dateOfBirth: new DateTimeImmutable('2015-01-01'),
    );
}

it('lista los menores vinculados activamente al tutor autenticado', function (): void {
    $relationships = new InMemoryGuardianRelationshipRepositoryForList;
    $relationships->save(GuardianRelationship::create(id: 'r-1', guardianUserId: 'guardian-1', minorUserId: 'minor-1'));
    $relationships->save(GuardianRelationship::create(id: 'r-2', guardianUserId: 'guardian-1', minorUserId: 'minor-2'));
    $relationships->save(GuardianRelationship::create(id: 'r-3', guardianUserId: 'otro-tutor', minorUserId: 'minor-3'));

    $users = new InMemoryUserRepositoryForList;
    $users->save(listLinkedMinorTestUser('minor-1', 'Menor Uno'));
    $users->save(listLinkedMinorTestUser('minor-2', 'Menor Dos'));
    $users->save(listLinkedMinorTestUser('minor-3', 'Menor Tres'));

    $handler = new ListMyLinkedMinorsHandler($relationships, $users);
    $response = $handler->handle(new ListMyLinkedMinorsQuery(guardianUserId: 'guardian-1'));

    expect($response)->toHaveCount(2)
        ->and($response[0]->name)->toBe('Menor Uno')
        ->and($response[1]->name)->toBe('Menor Dos');
});

it('no incluye relaciones revocadas en el listado', function (): void {
    $relationships = new InMemoryGuardianRelationshipRepositoryForList;
    $relationship = GuardianRelationship::create(id: 'r-1', guardianUserId: 'guardian-1', minorUserId: 'minor-1');
    $relationship->revoke(new DateTimeImmutable);
    $relationships->save($relationship);

    $users = new InMemoryUserRepositoryForList;
    $users->save(listLinkedMinorTestUser('minor-1', 'Menor Uno'));

    $handler = new ListMyLinkedMinorsHandler($relationships, $users);
    $response = $handler->handle(new ListMyLinkedMinorsQuery(guardianUserId: 'guardian-1'));

    expect($response)->toBeEmpty();
});
