<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Modules\Identity\Application\Commands\LoginUserCommand;
use Modules\Identity\Application\Responses\LoginUserResponse;
use Modules\Identity\Application\Services\AccessTokenIssuer;
use Modules\Identity\Application\Services\PasswordHasher;
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
            throw new InvalidCredentials;
        }

        if (! $user->status()->canAuthenticate()) {
            throw new UserCannotAuthenticate;
        }

        $issuedToken = $this->accessTokenIssuer->issue(
            $user->id(),
            $command->tokenName,
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
}
