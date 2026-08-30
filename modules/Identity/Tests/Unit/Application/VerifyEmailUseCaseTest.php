<?php

declare(strict_types=1);

use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\VerifyEmailCommand;
use Modules\Identity\Application\UseCases\VerifyEmailUseCase;
use Modules\Identity\Domain\Entities\EmailVerificationToken;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Enums\UserStatus;
use Modules\Identity\Domain\Exceptions\InvalidEmailVerificationToken;
use Modules\Identity\Domain\Repositories\EmailVerificationTokenRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

final class InMemoryUserRepositoryForVerify implements UserRepository
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

final class InMemoryEmailVerificationTokenRepositoryForVerify implements EmailVerificationTokenRepository
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

final class SpyAuditLoggerForVerify implements AuditLogger
{
    /** @var list<AuditEntry> */
    public array $entries = [];

    public function log(AuditEntry $entry): void
    {
        $this->entries[] = $entry;
    }
}

function registeredUnverifiedUserForVerify(): User
{
    return User::register(
        id: '01900000-0000-7000-8000-000000000001',
        name: 'Abel Campos',
        email: Email::fromString('abel@edudrive.cr'),
        passwordHash: 'hashed-password',
    );
}

/** @return array{0: VerifyEmailUseCase, 1: InMemoryUserRepositoryForVerify, 2: InMemoryEmailVerificationTokenRepositoryForVerify, 3: SpyAuditLoggerForVerify} */
function buildVerifyEmailUseCase(): array
{
    $users = new InMemoryUserRepositoryForVerify;
    $tokens = new InMemoryEmailVerificationTokenRepositoryForVerify;
    $audit = new SpyAuditLoggerForVerify;

    return [new VerifyEmailUseCase($users, $tokens, $audit), $users, $tokens, $audit];
}

it('activa al usuario, registra la fecha de verificacion y borra el token', function (): void {
    [$useCase, $users, $tokens, $audit] = buildVerifyEmailUseCase();

    $user = registeredUnverifiedUserForVerify();
    $users->save($user);
    $tokens->save(EmailVerificationToken::issue(
        email: $user->email(),
        tokenHash: hash('sha256', 'token-valido'),
    ));

    $useCase->execute(new VerifyEmailCommand(email: 'abel@edudrive.cr', token: 'token-valido'));

    $persisted = $users->findById($user->id());

    expect($persisted?->status())->toBe(UserStatus::Active)
        ->and($persisted?->emailVerifiedAt())->not->toBeNull()
        ->and($tokens->findByEmail($user->email()))->toBeNull()
        ->and($audit->entries)->toHaveCount(1)
        ->and($audit->entries[0]->action)->toBe('auth.email_verified')
        ->and($audit->entries[0]->outcome)->toBe('success');
});

it('rechaza un token que no coincide', function (): void {
    [$useCase, $users, $tokens] = buildVerifyEmailUseCase();

    $user = registeredUnverifiedUserForVerify();
    $users->save($user);
    $tokens->save(EmailVerificationToken::issue(
        email: $user->email(),
        tokenHash: hash('sha256', 'token-valido'),
    ));

    $useCase->execute(new VerifyEmailCommand(email: 'abel@edudrive.cr', token: 'token-incorrecto'));
})->throws(InvalidEmailVerificationToken::class);

it('rechaza un token expirado', function (): void {
    [$useCase, $users, $tokens] = buildVerifyEmailUseCase();

    $user = registeredUnverifiedUserForVerify();
    $users->save($user);
    $tokens->save(EmailVerificationToken::reconstitute(
        email: $user->email(),
        tokenHash: hash('sha256', 'token-valido'),
        createdAt: new DateTimeImmutable('-2 hours'),
    ));

    $useCase->execute(new VerifyEmailCommand(email: 'abel@edudrive.cr', token: 'token-valido'));
})->throws(InvalidEmailVerificationToken::class);

it('rechaza cuando no hay ningun token solicitado para el correo', function (): void {
    [$useCase, $users] = buildVerifyEmailUseCase();
    $users->save(registeredUnverifiedUserForVerify());

    $useCase->execute(new VerifyEmailCommand(email: 'abel@edudrive.cr', token: 'cualquier-token'));
})->throws(InvalidEmailVerificationToken::class);

it('rechaza cuando el usuario no existe, con el mismo error que un token invalido', function (): void {
    [$useCase] = buildVerifyEmailUseCase();

    $useCase->execute(new VerifyEmailCommand(email: 'no-existe@edudrive.cr', token: 'cualquier-token'));
})->throws(InvalidEmailVerificationToken::class);
