<?php

declare(strict_types=1);

use Modules\Academic\Application\Commands\AssignGroupTeacherCommand;
use Modules\Academic\Application\Exceptions\GroupNotFound;
use Modules\Academic\Application\UseCases\AssignGroupTeacherHandler;
use Modules\Academic\Domain\Aggregates\Group;
use Modules\Academic\Domain\Repositories\GroupRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\GroupId;

final class InMemoryGroupRepositoryForAssignTeacher implements GroupRepository
{
    /** @var array<string, Group> */
    public array $groups = [];

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
        return array_values($this->groups);
    }
}

function assignTeacherTestGroup(): Group
{
    return Group::create(
        id: GroupId::fromString('01981a64-8300-7b1d-b442-764ea7f92111'),
        courseId: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f92112'),
        organizationId: null,
        name: 'Grupo A',
        teacherId: null,
        startsAt: new DateTimeImmutable('2026-01-15'),
        endsAt: new DateTimeImmutable('2026-06-15'),
    );
}

it('reasigna el docente de un grupo existente', function (): void {
    $groups = new InMemoryGroupRepositoryForAssignTeacher;
    $groups->save(assignTeacherTestGroup());

    $handler = new AssignGroupTeacherHandler($groups);
    $response = $handler->handle(new AssignGroupTeacherCommand(
        groupId: '01981a64-8300-7b1d-b442-764ea7f92111',
        teacherId: 'teacher-1',
    ));

    expect($response->teacherId)->toBe('teacher-1');
});

it('rechaza reasignar el docente de un grupo inexistente', function (): void {
    $handler = new AssignGroupTeacherHandler(new InMemoryGroupRepositoryForAssignTeacher);

    $handler->handle(new AssignGroupTeacherCommand(
        groupId: '01981a64-8300-7b1d-b442-764ea7f92111',
        teacherId: 'teacher-1',
    ));
})->throws(GroupNotFound::class);
