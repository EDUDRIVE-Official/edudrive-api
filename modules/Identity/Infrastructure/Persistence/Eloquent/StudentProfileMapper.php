<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Eloquent;

use Modules\Identity\Domain\Entities\StudentProfile;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\StudentProfileModel;

final class StudentProfileMapper
{
    public static function toDomain(StudentProfileModel $model): StudentProfile
    {
        return StudentProfile::restore(
            userId: $model->user_id,
            educationLevel: $model->education_level,
            accessibilityNeeds: $model->accessibility_needs,
            learningPreferences: $model->learning_preferences,
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }

    /**
     * @return array<string, string|null>
     */
    public static function toPersistence(StudentProfile $profile): array
    {
        return [
            'user_id' => $profile->userId(),
            'education_level' => $profile->educationLevel(),
            'accessibility_needs' => $profile->accessibilityNeeds(),
            'learning_preferences' => $profile->learningPreferences(),
        ];
    }
}
