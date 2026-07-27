<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Services;

interface AccessTokenRevoker
{
    public function revokeCurrent(string $tokenId): void;
}
