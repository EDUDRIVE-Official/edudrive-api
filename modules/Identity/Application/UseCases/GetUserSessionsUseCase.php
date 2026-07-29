<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Modules\Identity\Application\DTO\SessionData;
use Modules\Identity\Application\Services\SessionRepository;

final readonly class GetUserSessionsUseCase
{
    public function __construct(
        private SessionRepository $sessionRepository,
    ) {}

    /**
     * @return array<SessionData>
     */
    public function execute(
        string $userId,
        ?string $currentTokenId = null,
    ): array {
        return $this->sessionRepository->findByUser(
            userId: $userId,
            currentTokenId: $currentTokenId,
        );
    }
}
