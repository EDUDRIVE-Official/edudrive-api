<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Services\AccessTokenRevoker;

final readonly class LogoutUserUseCase
{
    public function __construct(
        private AccessTokenRevoker $tokens,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(string $tokenId, string $userId): void
    {
        $this->tokens->revokeCurrent($tokenId);

        $this->auditLogger->log(
            new AuditEntry(
                action: 'auth.logout',
                userId: $userId,
                entity: 'User',
                entityId: $userId,
                metadata: [
                    'token_id' => $tokenId,
                ],
            ),
        );
    }
}
