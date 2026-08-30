<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\AssignGroupTeacherCommand;
use Modules\Academic\Application\Exceptions\GroupNotFound;
use Modules\Academic\Application\Responses\GroupResponse;
use Modules\Academic\Domain\Repositories\GroupRepository;
use Modules\Academic\Domain\ValueObjects\GroupId;

final readonly class AssignGroupTeacherHandler
{
    public function __construct(
        private GroupRepository $groups,
    ) {}

    public function handle(AssignGroupTeacherCommand $command): GroupResponse
    {
        $group = $this->groups->findById(GroupId::fromString($command->groupId));

        if ($group === null) {
            throw GroupNotFound::withId($command->groupId);
        }

        $group->assignTeacher($command->teacherId);

        $this->groups->save($group);

        return GroupResponse::fromGroup($group);
    }
}
