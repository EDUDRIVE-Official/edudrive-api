<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\CreateGroupCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Responses\GroupResponse;
use Modules\Academic\Domain\Aggregates\Group;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\GroupRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\GroupId;

final readonly class CreateGroupHandler
{
    public function __construct(
        private GroupRepository $groups,
        private CourseRepository $courses,
    ) {}

    public function handle(CreateGroupCommand $command): GroupResponse
    {
        $courseId = CourseId::fromString($command->courseId);

        if ($this->courses->findById($courseId) === null) {
            throw CourseNotFound::withId($command->courseId);
        }

        $group = Group::create(
            id: GroupId::fromString((string) Str::uuid()),
            courseId: $courseId,
            organizationId: $command->organizationId,
            name: $command->name,
            teacherId: $command->teacherId,
            startsAt: new DateTimeImmutable($command->startsAt),
            endsAt: new DateTimeImmutable($command->endsAt),
        );

        $this->groups->save($group);

        return GroupResponse::fromGroup($group);
    }
}
