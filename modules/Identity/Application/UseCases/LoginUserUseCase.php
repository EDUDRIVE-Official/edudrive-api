<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use DateTimeImmutable;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\LoginUserCommand;
use Modules\Identity\Application\Responses\LoginUserResponse;
use Modules\Identity\Application\Services\AccessTokenIssuer;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Exceptions\InvalidCredentials;
use Modules\Identity\Domain\Exceptions\UserCannotAuthenticate;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

final readonly class LoginUserUseCase
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwordHasher,
        private AccessTokenIssuer $accessTokenIssuer,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(LoginUserCommand $command): LoginUserResponse
    {
        $user = $this->users->findByEmail(
            Email::fromString($command->email),
        );

        if (
            $user === null
            || ! $this->passwordHasher->verify(
                $command->password,
                $user->passwordHash(),
            )
        ) {
            $this->logFailedLogin($command, $user);

            throw new InvalidCredentials;
        }

        if (! $user->status()->canAuthenticate()) {
            $this->logFailedLogin($command, $user);

            throw new UserCannotAuthenticate;
        }

        $issuedToken = $this->accessTokenIssuer->issue(
            $user->id(),
            $command->tokenName,
        );

        $user->recordLogin(new DateTimeImmutable);
        $this->users->save($user);

        $this->auditLogger->log(
            new AuditEntry(
                action: 'auth.login',
                userId: $user->id(),
                entity: 'User',
                entityId: $user->id(),
                metadata: [
                    'token_name' => $command->tokenName,
                ],
            ),
        );

        return new LoginUserResponse(
            userId: $user->id(),
            name: $user->name(),
            email: $user->email()->value(),
            status: $user->status()->value,
            accessToken: $issuedToken->plainTextToken,
            tokenType: $issuedToken->tokenType,
        );
    }

    private function logFailedLogin(LoginUserCommand $command, ?User $user): void
    {
        $this->auditLogger->log(
            new AuditEntry(
                action: 'auth.login',
                userId: $user?->id(),
                entity: 'User',
                entityId: $user?->id(),
                metadata: [
                    'email' => $command->email,
                ],
                outcome: 'failure',
            ),
        );
    }
}
