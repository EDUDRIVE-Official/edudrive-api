<?php

declare(strict_types=1);

use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\RequestPasswordResetCommand;
use Modules\Identity\Application\UseCases\RequestPasswordResetUseCase;
use Modules\Identity\Domain\Entities\PasswordResetToken;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\PasswordResetTokenRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Notification\Application\Services\EmailNotificationSender;

final class InMemoryUserRepositoryForPasswordReset implements UserRepository
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
        foreach ($this->items as $user) {
            if ($user->email()->equals($email)) {
                return $user;
            }
        }

        return null;
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->findByEmail($email) !== null;
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

final class InMemoryPasswordResetTokenRepositoryForRequest implements PasswordResetTokenRepository
{
    /** @var array<string, PasswordResetToken> */
    public array $items = [];

    public function save(PasswordResetToken $token): void
    {
        $this->items[$token->email()->value()] = $token;
    }

    public function findByEmail(Email $email): ?PasswordResetToken
    {
        return $this->items[$email->value()] ?? null;
    }

    public function deleteByEmail(Email $email): void
    {
        unset($this->items[$email->value()]);
    }
}

final class SpyAuditLoggerForPasswordReset implements AuditLogger
{
    /** @var list<AuditEntry> */
    public array $entries = [];

    public function log(AuditEntry $entry): void
    {
        $this->entries[] = $entry;
    }
}

final class SpyEmailNotificationSenderForPasswordReset implements EmailNotificationSender
{
    /** @var list<array{userId: string, subject: string, body: string}> */
    public array $sent = [];

    public function send(string $userId, string $subject, string $body): void
    {
        $this->sent[] = ['userId' => $userId, 'subject' => $subject, 'body' => $body];
    }
}

function registeredPasswordResetUser(): User
{
    return User::register(
        id: '01900000-0000-7000-8000-000000000001',
        name: 'Abel Campos',
        email: Email::fromString('abel@edudrive.cr'),
        passwordHash: 'hashed-password',
    );
}

it('genera y guarda un token, y envia el correo cuando el usuario existe', function (): void {
    $users = new InMemoryUserRepositoryForPasswordReset;
    $users->save(registeredPasswordResetUser());
    $tokens = new InMemoryPasswordResetTokenRepositoryForRequest;
    $audit = new SpyAuditLoggerForPasswordReset;
    $mailer = new SpyEmailNotificationSenderForPasswordReset;

    $useCase = new RequestPasswordResetUseCase($users, $tokens, $audit, $mailer);
    $useCase->execute(new RequestPasswordResetCommand(email: 'abel@edudrive.cr'));

    expect($tokens->items)->toHaveCount(1)
        ->and($mailer->sent)->toHaveCount(1)
        ->and($mailer->sent[0]['userId'])->toBe('01900000-0000-7000-8000-000000000001')
        ->and($audit->entries)->toHaveCount(1)
        ->and($audit->entries[0]->action)->toBe('auth.password_reset_requested')
        ->and($audit->entries[0]->outcome)->toBe('success');
});

it('reemplaza cualquier token previo del mismo usuario', function (): void {
    $users = new InMemoryUserRepositoryForPasswordReset;
    $users->save(registeredPasswordResetUser());
    $tokens = new InMemoryPasswordResetTokenRepositoryForRequest;
    $useCase = new RequestPasswordResetUseCase(
        $users,
        $tokens,
        new SpyAuditLoggerForPasswordReset,
        new SpyEmailNotificationSenderForPasswordReset,
    );

    $useCase->execute(new RequestPasswordResetCommand(email: 'abel@edudrive.cr'));
    $firstHash = $tokens->findByEmail(Email::fromString('abel@edudrive.cr'))?->tokenHash();

    $useCase->execute(new RequestPasswordResetCommand(email: 'abel@edudrive.cr'));
    $secondHash = $tokens->findByEmail(Email::fromString('abel@edudrive.cr'))?->tokenHash();

    expect($tokens->items)->toHaveCount(1)
        ->and($secondHash)->not->toBe($firstHash);
});

it('no envia correo ni guarda token cuando el usuario no existe, pero audita el intento', function (): void {
    $tokens = new InMemoryPasswordResetTokenRepositoryForRequest;
    $audit = new SpyAuditLoggerForPasswordReset;
    $mailer = new SpyEmailNotificationSenderForPasswordReset;

    $useCase = new RequestPasswordResetUseCase(
        new InMemoryUserRepositoryForPasswordReset,
        $tokens,
        $audit,
        $mailer,
    );
    $useCase->execute(new RequestPasswordResetCommand(email: 'no-existe@edudrive.cr'));

    expect($tokens->items)->toBeEmpty()
        ->and($mailer->sent)->toBeEmpty()
        ->and($audit->entries)->toHaveCount(1)
        ->and($audit->entries[0]->outcome)->toBe('failure')
        ->and($audit->entries[0]->userId)->toBeNull();
});
