<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Repositories;

use Modules\Identity\Domain\Entities\TeacherProfile;
use Modules\Identity\Domain\Repositories\TeacherProfileRepository;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\TeacherProfileModel;
use Modules\Identity\Infrastructure\Persistence\Eloquent\TeacherProfileMapper;

final class EloquentTeacherProfileRepository implements TeacherProfileRepository
{
    public function save(TeacherProfile $profile): void
    {
        TeacherProfileModel::query()->updateOrCreate(
            ['user_id' => $profile->userId()],
            TeacherProfileMapper::toPersistence($profile),
        );
    }

    public function findByUserId(string $userId): ?TeacherProfile
    {
        $model = TeacherProfileModel::query()->find($userId);

        return $model instanceof TeacherProfileModel
            ? TeacherProfileMapper::toDomain($model)
            : null;
    }
}
