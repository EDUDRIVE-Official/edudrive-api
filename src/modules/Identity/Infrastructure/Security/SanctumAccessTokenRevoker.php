<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Security;

use Laravel\Sanctum\PersonalAccessToken;
use Modules\Identity\Application\Services\AccessTokenRevoker;

final class SanctumAccessTokenRevoker implements AccessTokenRevoker
{
    public function revokeCurrent(string $tokenId): void
    {
        PersonalAccessToken::query()
            ->whereKey($tokenId)
            ->delete();
    }
}
