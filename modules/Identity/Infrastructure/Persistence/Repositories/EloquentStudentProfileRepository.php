<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Repositories;

use Modules\Identity\Domain\Entities\StudentProfile;
use Modules\Identity\Domain\Repositories\StudentProfileRepository;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\StudentProfileModel;
use Modules\Identity\Infrastructure\Persistence\Eloquent\StudentProfileMapper;

final class EloquentStudentProfileRepository implements StudentProfileRepository
{
    public function save(StudentProfile $profile): void
    {
        StudentProfileModel::query()->updateOrCreate(
            ['user_id' => $profile->userId()],
            StudentProfileMapper::toPersistence($profile),
        );
    }

    public function findByUserId(string $userId): ?StudentProfile
    {
        $model = StudentProfileModel::query()->find($userId);

        return $model instanceof StudentProfileModel
            ? StudentProfileMapper::toDomain($model)
            : null;
    }
}
