<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Exceptions\SessionNotFound;
use Modules\Identity\Application\Services\AccessTokenRevoker;

final readonly class RevokeSessionUseCase
{
    public function __construct(
        private AccessTokenRevoker $tokens,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(string $tokenId, string $userId): void
    {
        if (! $this->tokens->revokeForUser($userId, $tokenId)) {
            throw new SessionNotFound;
        }

        $this->auditLogger->log(
            new AuditEntry(
                action: 'auth.session_revoked',
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
