<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\ListGroupsQuery;
use Modules\Academic\Application\Responses\GroupResponse;
use Modules\Academic\Domain\Aggregates\Group;
use Modules\Academic\Domain\Repositories\GroupRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class ListGroupsHandler
{
    public function __construct(
        private GroupRepository $groups,
    ) {}

    /** @return list<GroupResponse> */
    public function handle(ListGroupsQuery $query): array
    {
        $courseId = $query->courseId === null ? null : CourseId::fromString($query->courseId);

        return array_map(
            static fn (Group $group): GroupResponse => GroupResponse::fromGroup($group),
            $this->groups->all($courseId),
        );
    }
}
