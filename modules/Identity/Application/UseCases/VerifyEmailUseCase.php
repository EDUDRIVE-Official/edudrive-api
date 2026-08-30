<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use DateTimeImmutable;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\VerifyEmailCommand;
use Modules\Identity\Domain\Exceptions\InvalidEmailVerificationToken;
use Modules\Identity\Domain\Repositories\EmailVerificationTokenRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

final readonly class VerifyEmailUseCase
{
    public function __construct(
        private UserRepository $users,
        private EmailVerificationTokenRepository $tokens,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(VerifyEmailCommand $command): void
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
                action: 'auth.email_verified',
                userId: $user?->id(),
                entity: 'User',
                entityId: $user?->id(),
                metadata: ['email' => $command->email],
                outcome: 'failure',
            ));

            throw new InvalidEmailVerificationToken;
        }

        $user->activate($now);
        $this->users->save($user);
        $this->tokens->deleteByEmail($email);

        $this->auditLogger->log(new AuditEntry(
            action: 'auth.email_verified',
            userId: $user->id(),
            entity: 'User',
            entityId: $user->id(),
        ));
    }
}
