<?php

declare(strict_types=1);

use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\SendEmailVerificationCommand;
use Modules\Identity\Application\UseCases\SendEmailVerificationUseCase;
use Modules\Identity\Domain\Entities\EmailVerificationToken;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\EmailVerificationTokenRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Notification\Application\Services\EmailNotificationSender;

final class InMemoryUserRepositoryForSendVerification implements UserRepository
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

final class InMemoryEmailVerificationTokenRepositoryForSend implements EmailVerificationTokenRepository
{
    /** @var array<string, EmailVerificationToken> */
    public array $items = [];

    public function save(EmailVerificationToken $token): void
    {
        $this->items[$token->email()->value()] = $token;
    }

    public function findByEmail(Email $email): ?EmailVerificationToken
    {
        return $this->items[$email->value()] ?? null;
    }

    public function deleteByEmail(Email $email): void
    {
        unset($this->items[$email->value()]);
    }
}

final class SpyAuditLoggerForSendVerification implements AuditLogger
{
    /** @var list<AuditEntry> */
    public array $entries = [];

    public function log(AuditEntry $entry): void
    {
        $this->entries[] = $entry;
    }
}

final class SpyEmailNotificationSenderForSendVerification implements EmailNotificationSender
{
    /** @var list<array{userId: string, subject: string, body: string}> */
    public array $sent = [];

    public function send(string $userId, string $subject, string $body): void
    {
        $this->sent[] = ['userId' => $userId, 'subject' => $subject, 'body' => $body];
    }
}

function registeredUnverifiedUser(): User
{
    return User::register(
        id: '01900000-0000-7000-8000-000000000001',
        name: 'Abel Campos',
        email: Email::fromString('abel@edudrive.cr'),
        passwordHash: 'hashed-password',
    );
}

it('genera y guarda un token, y envia el correo cuando el usuario existe y no esta verificado', function (): void {
    $users = new InMemoryUserRepositoryForSendVerification;
    $users->save(registeredUnverifiedUser());
    $tokens = new InMemoryEmailVerificationTokenRepositoryForSend;
    $audit = new SpyAuditLoggerForSendVerification;
    $mailer = new SpyEmailNotificationSenderForSendVerification;

    $useCase = new SendEmailVerificationUseCase($users, $tokens, $audit, $mailer);
    $useCase->execute(new SendEmailVerificationCommand(email: 'abel@edudrive.cr'));

    expect($tokens->items)->toHaveCount(1)
        ->and($mailer->sent)->toHaveCount(1)
        ->and($mailer->sent[0]['userId'])->toBe('01900000-0000-7000-8000-000000000001')
        ->and($audit->entries)->toHaveCount(1)
        ->and($audit->entries[0]->action)->toBe('auth.email_verification_requested');
});

it('reemplaza cualquier token previo del mismo usuario', function (): void {
    $users = new InMemoryUserRepositoryForSendVerification;
    $users->save(registeredUnverifiedUser());
    $tokens = new InMemoryEmailVerificationTokenRepositoryForSend;
    $useCase = new SendEmailVerificationUseCase(
        $users,
        $tokens,
        new SpyAuditLoggerForSendVerification,
        new SpyEmailNotificationSenderForSendVerification,
    );

    $useCase->execute(new SendEmailVerificationCommand(email: 'abel@edudrive.cr'));
    $firstHash = $tokens->findByEmail(Email::fromString('abel@edudrive.cr'))?->tokenHash();

    $useCase->execute(new SendEmailVerificationCommand(email: 'abel@edudrive.cr'));
    $secondHash = $tokens->findByEmail(Email::fromString('abel@edudrive.cr'))?->tokenHash();

    expect($tokens->items)->toHaveCount(1)
        ->and($secondHash)->not->toBe($firstHash);
});

it('no hace nada cuando el usuario no existe', function (): void {
    $tokens = new InMemoryEmailVerificationTokenRepositoryForSend;
    $mailer = new SpyEmailNotificationSenderForSendVerification;

    $useCase = new SendEmailVerificationUseCase(
        new InMemoryUserRepositoryForSendVerification,
        $tokens,
        new SpyAuditLoggerForSendVerification,
        $mailer,
    );
    $useCase->execute(new SendEmailVerificationCommand(email: 'no-existe@edudrive.cr'));

    expect($tokens->items)->toBeEmpty()
        ->and($mailer->sent)->toBeEmpty();
});

it('no hace nada cuando el correo ya esta verificado', function (): void {
    $users = new InMemoryUserRepositoryForSendVerification;
    $user = registeredUnverifiedUser();
    $user->activate(new DateTimeImmutable);
    $users->save($user);
    $tokens = new InMemoryEmailVerificationTokenRepositoryForSend;
    $mailer = new SpyEmailNotificationSenderForSendVerification;

    $useCase = new SendEmailVerificationUseCase(
        $users,
        $tokens,
        new SpyAuditLoggerForSendVerification,
        $mailer,
    );
    $useCase->execute(new SendEmailVerificationCommand(email: 'abel@edudrive.cr'));

    expect($tokens->items)->toBeEmpty()
        ->and($mailer->sent)->toBeEmpty();
});
