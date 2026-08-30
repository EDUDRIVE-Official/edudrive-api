<?php

declare(strict_types=1);

namespace Modules\Legal\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Legal\Domain\Entities\UserConsent;
use Modules\Legal\Domain\Repositories\UserConsentRepository;
use Modules\Legal\Domain\ValueObjects\PolicyKey;
use Modules\Legal\Infrastructure\Persistence\Eloquent\Models\UserConsentModel;

final class EloquentUserConsentRepository implements UserConsentRepository
{
    public function save(UserConsent $consent): void
    {
        UserConsentModel::query()->updateOrCreate(
            ['id' => $consent->id()],
            [
                'user_id' => $consent->userId(),
                'policy_key' => $consent->policyKey()->value(),
                'policy_version' => $consent->policyVersion(),
                'accepted_at' => $consent->acceptedAt(),
                'guardian_declaration' => $consent->guardianDeclaration(),
                'revoked_at' => $consent->revokedAt(),
            ],
        );
    }

    /** @return list<UserConsent> */
    public function findByUserId(string $userId): array
    {
        return array_values(
            UserConsentModel::query()
                ->where('user_id', $userId)
                ->orderBy('accepted_at')
                ->get()
                ->map(fn (UserConsentModel $model): UserConsent => $this->toDomain($model))
                ->all(),
        );
    }

    public function findLatestActiveByUserAndPolicy(string $userId, PolicyKey $policyKey): ?UserConsent
    {
        $model = UserConsentModel::query()
            ->where('user_id', $userId)
            ->where('policy_key', $policyKey->value())
            ->whereNull('revoked_at')
            ->orderByDesc('accepted_at')
            ->first();

        return $model instanceof UserConsentModel ? $this->toDomain($model) : null;
    }

    private function toDomain(UserConsentModel $model): UserConsent
    {
        return UserConsent::restore(
            id: (string) $model->getAttribute('id'),
            userId: (string) $model->getAttribute('user_id'),
            policyKey: PolicyKey::fromString((string) $model->getAttribute('policy_key')),
            policyVersion: (int) $model->getAttribute('policy_version'),
            acceptedAt: $model->accepted_at->toDateTimeImmutable(),
            guardianDeclaration: $model->getAttribute('guardian_declaration') === null
                ? null
                : (string) $model->getAttribute('guardian_declaration'),
            revokedAt: $model->revoked_at?->toDateTimeImmutable(),
        );
    }
}
