<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Modules\Identity\Application\Responses\UserResponse;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;

final readonly class ListUsersUseCase
{
    public function __construct(
        private UserRepository $users,
    ) {}

    /** @return list<UserResponse> */
    public function execute(): array
    {
        return array_map(
            static fn (User $user): UserResponse => UserResponse::fromUser($user),
            $this->users->all(),
        );
    }
}
