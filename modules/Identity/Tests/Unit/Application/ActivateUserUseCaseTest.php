<?php

declare(strict_types=1);

use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\ActivateUserCommand;
use Modules\Identity\Application\UseCases\ActivateUserUseCase;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Exceptions\UserNotFound;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

final class InMemoryUserRepositoryForActivate implements UserRepository
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

final class SpyAuditLoggerForActivate implements AuditLogger
{
    /** @var list<AuditEntry> */
    public array $entries = [];

    public function log(AuditEntry $entry): void
    {
        $this->entries[] = $entry;
    }
}

it('activa un usuario y audita la operacion con el id de quien la realiza', function (): void {
    $users = new InMemoryUserRepositoryForActivate;
    $user = User::register(
        id: 'user-1',
        name: 'Usuario de prueba',
        email: Email::fromString('abel@edudrive.cr'),
        passwordHash: 'hashed-password',
    );
    $users->save($user);
    $audit = new SpyAuditLoggerForActivate;

    $useCase = new ActivateUserUseCase($users, $audit);
    $useCase->execute(new ActivateUserCommand(userId: 'user-1', actorId: 'admin-1'));

    expect($audit->entries)->toHaveCount(1)
        ->and($audit->entries[0]->action)->toBe('identity.account_activated')
        ->and($audit->entries[0]->userId)->toBe('admin-1')
        ->and($audit->entries[0]->entityId)->toBe('user-1');
});

it('rechaza activar un usuario inexistente', function (): void {
    $useCase = new ActivateUserUseCase(new InMemoryUserRepositoryForActivate, new SpyAuditLoggerForActivate);

    $useCase->execute(new ActivateUserCommand(userId: 'no-existe', actorId: 'admin-1'));
})->throws(UserNotFound::class);
