<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Repositories;

use Modules\Identity\Domain\Entities\TeacherProfile;

interface TeacherProfileRepository
{
    public function save(TeacherProfile $profile): void;

    public function findByUserId(string $userId): ?TeacherProfile;
}
