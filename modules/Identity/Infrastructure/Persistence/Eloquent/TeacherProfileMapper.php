<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Eloquent;

use Modules\Identity\Domain\Entities\TeacherProfile;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\TeacherProfileModel;

final class TeacherProfileMapper
{
    public static function toDomain(TeacherProfileModel $model): TeacherProfile
    {
        return TeacherProfile::restore(
            userId: $model->user_id,
            specialties: $model->specialties,
            certifications: $model->certifications,
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }

    /**
     * @return array<string, string|null>
     */
    public static function toPersistence(TeacherProfile $profile): array
    {
        return [
            'user_id' => $profile->userId(),
            'specialties' => $profile->specialties(),
            'certifications' => $profile->certifications(),
        ];
    }
}
