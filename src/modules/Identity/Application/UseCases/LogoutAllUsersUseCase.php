<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Modules\Identity\Application\Services\AccessTokenRevoker;

final readonly class LogoutAllUsersUseCase
{
    public function __construct(
        private AccessTokenRevoker $accessTokenRevoker,
    ) {}

    public function execute(string $userId): void
    {
        $this->accessTokenRevoker->revokeAllForUser($userId);
    }
}
