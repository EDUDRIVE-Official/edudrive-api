<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Modules\Identity\Application\Responses\AuthenticatedUserResponse;
use Modules\Identity\Domain\Exceptions\UserNotFound;
use Modules\Identity\Domain\Repositories\UserRepository;

final readonly class GetAuthenticatedUserUseCase
{
    public function __construct(
        private UserRepository $users,
    ) {}

    public function execute(string $userId): AuthenticatedUserResponse
    {
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new UserNotFound;
        }

        return new AuthenticatedUserResponse(
            id: $user->id(),
            name: $user->name(),
            email: $user->email()->value(),
            status: $user->status()->value,
        );
    }
}
