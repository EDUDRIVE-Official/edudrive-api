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

    public function revokeAllForUser(string $userId): void
    {
        PersonalAccessToken::query()
            ->where('tokenable_type', '=', $this->userModelType())
            ->where('tokenable_id', '=', $userId)
            ->delete();
    }

    private function userModelType(): string
    {
        return config(
            'auth.providers.users.model',
            'Modules\\Identity\\Infrastructure\\Persistence\\Eloquent\\Models\\UserModel',
        );
    }
}
