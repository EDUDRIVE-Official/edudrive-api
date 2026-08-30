<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Services;

interface AccessTokenRevoker
{
    public function revokeCurrent(string $tokenId): void;

    public function revokeAllForUser(string $userId): void;

    public function revokeForUser(string $userId, string $tokenId): bool;
}
