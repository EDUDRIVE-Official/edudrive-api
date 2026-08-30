<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Academic\Domain\Aggregates\Group;
use Modules\Academic\Domain\Repositories\GroupRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\GroupId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\GroupModel;

final class EloquentGroupRepository implements GroupRepository
{
    public function save(Group $group): void
    {
        GroupModel::query()->updateOrCreate(
            ['id' => $group->id()->value()],
            [
                'course_id' => $group->courseId()->value(),
                'organization_id' => $group->organizationId(),
                'name' => $group->name(),
                'teacher_id' => $group->teacherId(),
                'starts_at' => $group->startsAt(),
                'ends_at' => $group->endsAt(),
            ],
        );
    }

    public function findById(GroupId $id): ?Group
    {
        $model = GroupModel::query()->find($id->value());

        return $model === null ? null : $this->toDomain($model);
    }

    public function all(?CourseId $courseId = null): array
    {
        $query = GroupModel::query()->orderBy('starts_at');

        if ($courseId !== null) {
            $query->where('course_id', $courseId->value());
        }

        return array_values(
            $query->get()->map(fn (GroupModel $model): Group => $this->toDomain($model))->all(),
        );
    }

    private function toDomain(GroupModel $model): Group
    {
        return Group::restore(
            id: GroupId::fromString((string) $model->getAttribute('id')),
            courseId: CourseId::fromString((string) $model->getAttribute('course_id')),
            organizationId: $model->getAttribute('organization_id'),
            name: (string) $model->getAttribute('name'),
            teacherId: $model->getAttribute('teacher_id'),
            startsAt: $model->getAttribute('starts_at')->toDateTimeImmutable(),
            endsAt: $model->getAttribute('ends_at')->toDateTimeImmutable(),
        );
    }
}
