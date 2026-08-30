<?php

declare(strict_types=1);

use Modules\Academic\Application\Queries\ListGroupsQuery;
use Modules\Academic\Application\UseCases\ListGroupsHandler;
use Modules\Academic\Domain\Aggregates\Group;
use Modules\Academic\Domain\Repositories\GroupRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\GroupId;

final class InMemoryGroupRepositoryForList implements GroupRepository
{
    /** @var array<string, Group> */
    public array $groups = [];

    /** @var list<CourseId> */
    public array $requestedCourseIds = [];

    public function save(Group $group): void
    {
        $this->groups[$group->id()->value()] = $group;
    }

    public function findById(GroupId $id): ?Group
    {
        return $this->groups[$id->value()] ?? null;
    }

    /** @return list<Group> */
    public function all(?CourseId $courseId = null, ?string $teacherId = null): array
    {
        if ($courseId !== null) {
            $this->requestedCourseIds[] = $courseId;
        }

        return array_values($this->groups);
    }
}

it('lista todos los grupos cuando no se especifica un curso', function (): void {
    $groups = new InMemoryGroupRepositoryForList;
    $groups->save(Group::create(
        id: GroupId::fromString('01981a64-8300-7b1d-b442-764ea7f92111'),
        courseId: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f92112'),
        organizationId: null,
        name: 'Grupo A',
        teacherId: null,
        startsAt: new DateTimeImmutable('2026-01-15'),
        endsAt: new DateTimeImmutable('2026-06-15'),
    ));

    $handler = new ListGroupsHandler($groups);
    $response = $handler->handle(new ListGroupsQuery);

    expect($response)->toHaveCount(1)
        ->and($groups->requestedCourseIds)->toBeEmpty();
});

it('filtra por curso cuando se especifica', function (): void {
    $groups = new InMemoryGroupRepositoryForList;
    $courseId = '01981a64-8300-7b1d-b442-764ea7f92112';

    $handler = new ListGroupsHandler($groups);
    $handler->handle(new ListGroupsQuery(courseId: $courseId));

    expect($groups->requestedCourseIds)->toHaveCount(1)
        ->and($groups->requestedCourseIds[0]->value())->toBe($courseId);
});
