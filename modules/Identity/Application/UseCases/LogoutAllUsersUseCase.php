<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Services\AccessTokenRevoker;

final readonly class LogoutAllUsersUseCase
{
    public function __construct(
        private AccessTokenRevoker $accessTokenRevoker,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(string $userId): void
    {
        $this->accessTokenRevoker->revokeAllForUser($userId);

        $this->auditLogger->log(
            new AuditEntry(
                action: 'auth.logout_all',
                userId: $userId,
                entity: 'User',
                entityId: $userId,
            ),
        );
    }
}
