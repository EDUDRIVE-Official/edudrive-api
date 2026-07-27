<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Modules\Identity\Application\Services\AccessTokenRevoker;

final readonly class LogoutUserUseCase
{
    public function __construct(
        private AccessTokenRevoker $tokens,
    ) {}

    public function execute(string $tokenId): void
    {
        $this->tokens->revokeCurrent($tokenId);
    }
}
