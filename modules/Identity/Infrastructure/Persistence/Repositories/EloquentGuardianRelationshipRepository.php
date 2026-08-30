<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Repositories;

use Modules\Identity\Domain\Entities\GuardianRelationship;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;
use Modules\Identity\Infrastructure\Persistence\Eloquent\GuardianRelationshipMapper;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\GuardianRelationshipModel;

final class EloquentGuardianRelationshipRepository implements GuardianRelationshipRepository
{
    public function save(GuardianRelationship $relationship): void
    {
        GuardianRelationshipModel::query()->updateOrCreate(
            ['id' => $relationship->id()],
            GuardianRelationshipMapper::toPersistence($relationship),
        );
    }

    public function findById(string $id): ?GuardianRelationship
    {
        $model = GuardianRelationshipModel::query()->find($id);

        return $model instanceof GuardianRelationshipModel
            ? GuardianRelationshipMapper::toDomain($model)
            : null;
    }

    public function findActiveByGuardianAndMinor(string $guardianUserId, string $minorUserId): ?GuardianRelationship
    {
        $model = GuardianRelationshipModel::query()
            ->where('guardian_user_id', $guardianUserId)
            ->where('minor_user_id', $minorUserId)
            ->whereNull('revoked_at')
            ->first();

        return $model instanceof GuardianRelationshipModel
            ? GuardianRelationshipMapper::toDomain($model)
            : null;
    }

    /** @return list<GuardianRelationship> */
    public function findActiveByGuardian(string $guardianUserId): array
    {
        return array_values(
            GuardianRelationshipModel::query()
                ->where('guardian_user_id', $guardianUserId)
                ->whereNull('revoked_at')
                ->orderBy('created_at')
                ->get()
                ->map(static fn (GuardianRelationshipModel $model): GuardianRelationship => GuardianRelationshipMapper::toDomain($model))
                ->all(),
        );
    }
}
