<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Security;

use Laravel\Sanctum\PersonalAccessToken;
use Modules\Identity\Application\DTO\SessionData;
use Modules\Identity\Application\Services\SessionRepository;

final class SanctumSessionRepository implements SessionRepository
{
    /**
     * @return array<SessionData>
     */
    public function findByUser(
        string $userId,
        ?string $currentTokenId = null,
    ): array {
        return PersonalAccessToken::query()
            ->where('tokenable_type', '=', $this->userModelType())
            ->where('tokenable_id', '=', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(
                static function (PersonalAccessToken $token) use ($currentTokenId): SessionData {
                    $createdAt = $token->created_at;

                    return new SessionData(
                        id: (string) $token->getKey(),
                        name: $token->name,
                        current: (string) $token->getKey() === $currentTokenId,
                        lastUsedAt: $token->last_used_at?->toIso8601String(),
                        createdAt: $createdAt?->toIso8601String() ?? '',
                    );
                },
            )
            ->all();
    }

    private function userModelType(): string
    {
        return config(
            'auth.providers.users.model',
            'Modules\\Identity\\Infrastructure\\Persistence\\Eloquent\\Models\\UserModel',
        );
    }
}
