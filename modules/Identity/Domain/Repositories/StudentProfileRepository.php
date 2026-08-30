<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Repositories;

use Modules\Identity\Domain\Entities\StudentProfile;

interface StudentProfileRepository
{
    public function save(StudentProfile $profile): void;

    public function findByUserId(string $userId): ?StudentProfile;
}
