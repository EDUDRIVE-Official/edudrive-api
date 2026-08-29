<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use DateTimeImmutable;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\ResetPasswordCommand;
use Modules\Identity\Application\Services\AccessTokenRevoker;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Exceptions\InvalidPasswordResetToken;
use Modules\Identity\Domain\Repositories\PasswordResetTokenRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

final readonly class ResetPasswordUseCase
{
    public function __construct(
        private UserRepository $users,
        private PasswordResetTokenRepository $tokens,
        private PasswordHasher $passwordHasher,
        private AccessTokenRevoker $accessTokenRevoker,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(ResetPasswordCommand $command): void
    {
        $email = Email::fromString($command->email);
        $user = $this->users->findByEmail($email);
        $token = $this->tokens->findByEmail($email);
        $now = new DateTimeImmutable;

        if (
            $user === null
            || $token === null
            || $token->isExpired($now)
            || ! $token->matchesHash(hash('sha256', $command->token))
        ) {
            $this->auditLogger->log(new AuditEntry(
                action: 'auth.password_reset',
                userId: $user?->id(),
                entity: 'User',
                entityId: $user?->id(),
                metadata: ['email' => $command->email],
                outcome: 'failure',
            ));

            throw new InvalidPasswordResetToken;
        }

        $user->changePasswordHash(
            $this->passwordHasher->hash($command->newPassword),
            $now,
        );
        $this->users->save($user);
        $this->tokens->deleteByEmail($email);
        $this->accessTokenRevoker->revokeAllForUser($user->id());

        $this->auditLogger->log(new AuditEntry(
            action: 'auth.password_reset',
            userId: $user->id(),
            entity: 'User',
            entityId: $user->id(),
        ));
    }
}
