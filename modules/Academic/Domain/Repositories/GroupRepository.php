<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Repositories;

use Modules\Academic\Domain\Aggregates\Group;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\GroupId;

interface GroupRepository
{
    public function save(Group $group): void;

    public function findById(GroupId $id): ?Group;

    /**
     * @return list<Group>
     */
    public function all(?CourseId $courseId = null, ?string $teacherId = null): array;
}
