<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Services;

use Modules\Identity\Application\Data\IssuedAccessToken;

interface AccessTokenIssuer
{
    /**
     * @param  list<string>  $abilities
     */
    public function issue(
        string $userId,
        string $tokenName,
        array $abilities = ['*'],
    ): IssuedAccessToken;
}
