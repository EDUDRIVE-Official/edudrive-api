<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Modules\Identity\Application\Responses\UserResponse;
use Modules\Identity\Domain\Exceptions\UserNotFound;
use Modules\Identity\Domain\Repositories\UserRepository;

final readonly class GetUserUseCase
{
    public function __construct(
        private UserRepository $users,
    ) {}

    public function execute(string $userId): UserResponse
    {
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new UserNotFound;
        }

        return UserResponse::fromUser($user);
    }
}
