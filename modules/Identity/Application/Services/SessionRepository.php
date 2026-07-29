<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Services;

use Modules\Identity\Application\DTO\SessionData;

interface SessionRepository
{
    /**
     * @return array<SessionData>
     */
    public function findByUser(
        string $userId,
        ?string $currentTokenId = null,
    ): array;
}
