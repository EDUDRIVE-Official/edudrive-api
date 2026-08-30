<?php

declare(strict_types=1);

use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\ResetPasswordCommand;
use Modules\Identity\Application\Services\AccessTokenRevoker;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Application\UseCases\ResetPasswordUseCase;
use Modules\Identity\Domain\Entities\PasswordResetToken;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Exceptions\InvalidPasswordResetToken;
use Modules\Identity\Domain\Repositories\PasswordResetTokenRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

final class InMemoryUserRepositoryForReset implements UserRepository
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

final class InMemoryPasswordResetTokenRepositoryForReset implements PasswordResetTokenRepository
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

final class SpyAuditLoggerForReset implements AuditLogger
{
    /** @var list<AuditEntry> */
    public array $entries = [];

    public function log(AuditEntry $entry): void
    {
        $this->entries[] = $entry;
    }
}

final class FakePasswordHasherForReset implements PasswordHasher
{
    public function hash(string $plainPassword): string
    {
        return 'hashed:'.$plainPassword;
    }

    public function verify(string $plainPassword, string $hashedPassword): bool
    {
        return $hashedPassword === 'hashed:'.$plainPassword;
    }
}

final class SpyAccessTokenRevokerForReset implements AccessTokenRevoker
{
    /** @var list<string> */
    public array $revokedForUsers = [];

    public function revokeCurrent(string $tokenId): void
    {
        // no aplica en estas pruebas
    }

    public function revokeAllForUser(string $userId): void
    {
        $this->revokedForUsers[] = $userId;
    }

    public function revokeForUser(string $userId, string $tokenId): bool
    {
        return false; // no aplica en estas pruebas
    }
}

function registeredResetUser(): User
{
    return User::register(
        id: '01900000-0000-7000-8000-000000000001',
        name: 'Abel Campos',
        email: Email::fromString('abel@edudrive.cr'),
        passwordHash: 'hashed-password-anterior',
    );
}

function buildResetUseCase(): array
{
    $users = new InMemoryUserRepositoryForReset;
    $tokens = new InMemoryPasswordResetTokenRepositoryForReset;
    $audit = new SpyAuditLoggerForReset;
    $hasher = new FakePasswordHasherForReset;
    $revoker = new SpyAccessTokenRevokerForReset;

    return [
        new ResetPasswordUseCase($users, $tokens, $hasher, $revoker, $audit),
        $users,
        $tokens,
        $audit,
        $revoker,
    ];
}

it('restablece la contrasena, invalida el token y revoca todas las sesiones', function (): void {
    [$useCase, $users, $tokens, $audit, $revoker] = buildResetUseCase();

    $user = registeredResetUser();
    $users->save($user);
    $tokens->save(PasswordResetToken::issue(
        email: $user->email(),
        tokenHash: hash('sha256', 'token-valido'),
    ));

    $useCase->execute(new ResetPasswordCommand(
        email: 'abel@edudrive.cr',
        token: 'token-valido',
        newPassword: 'clave-nueva-123',
    ));

    expect($users->findById($user->id())?->passwordHash())->toBe('hashed:clave-nueva-123')
        ->and($tokens->findByEmail($user->email()))->toBeNull()
        ->and($revoker->revokedForUsers)->toBe([$user->id()])
        ->and($audit->entries)->toHaveCount(1)
        ->and($audit->entries[0]->action)->toBe('auth.password_reset')
        ->and($audit->entries[0]->outcome)->toBe('success');
});

it('rechaza un token que no coincide', function (): void {
    [$useCase, $users, $tokens] = buildResetUseCase();

    $user = registeredResetUser();
    $users->save($user);
    $tokens->save(PasswordResetToken::issue(
        email: $user->email(),
        tokenHash: hash('sha256', 'token-valido'),
    ));

    $useCase->execute(new ResetPasswordCommand(
        email: 'abel@edudrive.cr',
        token: 'token-incorrecto',
        newPassword: 'clave-nueva-123',
    ));
})->throws(InvalidPasswordResetToken::class);

it('rechaza un token expirado', function (): void {
    [$useCase, $users, $tokens] = buildResetUseCase();

    $user = registeredResetUser();
    $users->save($user);
    $tokens->save(PasswordResetToken::reconstitute(
        email: $user->email(),
        tokenHash: hash('sha256', 'token-valido'),
        createdAt: new DateTimeImmutable('-2 hours'),
    ));

    $useCase->execute(new ResetPasswordCommand(
        email: 'abel@edudrive.cr',
        token: 'token-valido',
        newPassword: 'clave-nueva-123',
    ));
})->throws(InvalidPasswordResetToken::class);

it('rechaza cuando no hay ningun token solicitado para el correo', function (): void {
    [$useCase, $users] = buildResetUseCase();
    $users->save(registeredResetUser());

    $useCase->execute(new ResetPasswordCommand(
        email: 'abel@edudrive.cr',
        token: 'cualquier-token',
        newPassword: 'clave-nueva-123',
    ));
})->throws(InvalidPasswordResetToken::class);

it('rechaza cuando el usuario no existe, con el mismo error que un token invalido', function (): void {
    [$useCase] = buildResetUseCase();

    $useCase->execute(new ResetPasswordCommand(
        email: 'no-existe@edudrive.cr',
        token: 'cualquier-token',
        newPassword: 'clave-nueva-123',
    ));
})->throws(InvalidPasswordResetToken::class);
