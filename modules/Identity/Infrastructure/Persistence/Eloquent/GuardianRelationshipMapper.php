<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Eloquent;

use Modules\Identity\Domain\Entities\GuardianRelationship;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\GuardianRelationshipModel;

final class GuardianRelationshipMapper
{
    public static function toDomain(GuardianRelationshipModel $model): GuardianRelationship
    {
        return GuardianRelationship::restore(
            id: $model->id,
            guardianUserId: $model->guardian_user_id,
            minorUserId: $model->minor_user_id,
            createdAt: $model->created_at->toDateTimeImmutable(),
            revokedAt: $model->revoked_at?->toDateTimeImmutable(),
        );
    }

    /**
     * @return array<string, string|null>
     */
    public static function toPersistence(GuardianRelationship $relationship): array
    {
        return [
            'id' => $relationship->id(),
            'guardian_user_id' => $relationship->guardianUserId(),
            'minor_user_id' => $relationship->minorUserId(),
            'created_at' => $relationship->createdAt()->format('Y-m-d H:i:s'),
            'revoked_at' => $relationship->revokedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
