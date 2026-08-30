<?php

declare(strict_types=1);

use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Exceptions\SessionNotFound;
use Modules\Identity\Application\Services\AccessTokenRevoker;
use Modules\Identity\Application\UseCases\RevokeSessionUseCase;

final class FakeAccessTokenRevokerForRevokeSession implements AccessTokenRevoker
{
    /** @var list<array{userId: string, tokenId: string}> */
    public array $revokedForUser = [];

    public function __construct(
        private readonly bool $tokenBelongsToUser = true,
    ) {}

    public function revokeCurrent(string $tokenId): void
    {
        // no aplica en estas pruebas
    }

    public function revokeAllForUser(string $userId): void
    {
        // no aplica en estas pruebas
    }

    public function revokeForUser(string $userId, string $tokenId): bool
    {
        if (! $this->tokenBelongsToUser) {
            return false;
        }

        $this->revokedForUser[] = ['userId' => $userId, 'tokenId' => $tokenId];

        return true;
    }
}

final class SpyAuditLoggerForRevokeSession implements AuditLogger
{
    /** @var list<AuditEntry> */
    public array $entries = [];

    public function log(AuditEntry $entry): void
    {
        $this->entries[] = $entry;
    }
}

it('revoca la sesion y audita la operacion', function (): void {
    $revoker = new FakeAccessTokenRevokerForRevokeSession;
    $audit = new SpyAuditLoggerForRevokeSession;
    $useCase = new RevokeSessionUseCase($revoker, $audit);

    $useCase->execute(tokenId: 'token-1', userId: 'user-1');

    expect($revoker->revokedForUser)->toBe([['userId' => 'user-1', 'tokenId' => 'token-1']])
        ->and($audit->entries)->toHaveCount(1)
        ->and($audit->entries[0]->action)->toBe('auth.session_revoked')
        ->and($audit->entries[0]->userId)->toBe('user-1');
});

it('rechaza revocar una sesion que no pertenece al usuario', function (): void {
    $revoker = new FakeAccessTokenRevokerForRevokeSession(tokenBelongsToUser: false);
    $useCase = new RevokeSessionUseCase($revoker, new SpyAuditLoggerForRevokeSession);

    $useCase->execute(tokenId: 'token-ajeno', userId: 'user-1');
})->throws(SessionNotFound::class);
