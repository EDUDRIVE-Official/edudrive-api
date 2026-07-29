<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Security;

use Modules\Identity\Application\Data\IssuedAccessToken;
use Modules\Identity\Application\Services\AccessTokenIssuer;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use RuntimeException;

final class SanctumAccessTokenIssuer implements AccessTokenIssuer
{
    /**
     * @param  list<string>  $abilities
     */
    public function issue(
        string $userId,
        string $tokenName,
        array $abilities = ['*'],
    ): IssuedAccessToken {
        $user = UserModel::query()->find($userId);

        if (! $user instanceof UserModel) {
            throw new RuntimeException('Cannot issue an access token for an unknown user.');
        }

        $token = $user->createToken(
            $tokenName,
            $abilities,
        );

        return new IssuedAccessToken(
            plainTextToken: $token->plainTextToken,
        );
    }
}
