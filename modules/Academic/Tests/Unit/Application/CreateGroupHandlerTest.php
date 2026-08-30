<?php

declare(strict_types=1);

use Modules\Academic\Application\Commands\CreateGroupCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\UseCases\CreateGroupHandler;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Group;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\GroupRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\GroupId;

final class InMemoryCourseRepositoryForGroups implements CourseRepository
{
    /** @var array<string, Course> */
    private array $courses = [];

    /** @param list<Course> $courses */
    public function __construct(array $courses)
    {
        foreach ($courses as $course) {
            $this->courses[$course->id()->value()] = $course;
        }
    }

    public function save(Course $course): void
    {
        $this->courses[$course->id()->value()] = $course;
    }

    public function updateAtomically(CourseId $id, Closure $mutation): ?Course
    {
        return null;
    }

    public function updateAtomicallyWithContentCoverage(CourseId $id, Closure $mutation): ?Course
    {
        return null;
    }

    public function findById(CourseId $id): ?Course
    {
        return $this->courses[$id->value()] ?? null;
    }

    public function findByCode(CourseCode $code): ?Course
    {
        foreach ($this->courses as $course) {
            if ($course->code()->equals($code)) {
                return $course;
            }
        }

        return null;
    }

    public function existsByCode(CourseCode $code): bool
    {
        return $this->findByCode($code) !== null;
    }

    /** @return list<Course> */
    public function all(): array
    {
        return array_values($this->courses);
    }
}

final class InMemoryGroupRepositoryForCreate implements GroupRepository
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

function groupTestCourse(): Course
{
    return Course::create(
        id: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f92112'),
        code: CourseCode::fromString('COURSE-GROUP-01'),
        title: CourseTitle::fromString('Curso de prueba para grupos'),
    );
}

it('crea un grupo cuando el curso existe', function (): void {
    $course = groupTestCourse();
    $courses = new InMemoryCourseRepositoryForGroups([$course]);
    $groups = new InMemoryGroupRepositoryForCreate;

    $handler = new CreateGroupHandler($groups, $courses);
    $response = $handler->handle(new CreateGroupCommand(
        courseId: $course->id()->value(),
        organizationId: 'org-1',
        name: 'Grupo A',
        teacherId: 'teacher-1',
        startsAt: '2026-01-15T00:00:00+00:00',
        endsAt: '2026-06-15T00:00:00+00:00',
    ));

    expect($response->courseId)->toBe($course->id()->value())
        ->and($response->name)->toBe('Grupo A')
        ->and($response->teacherId)->toBe('teacher-1')
        ->and($groups->groups)->toHaveCount(1);
});

it('rechaza crear un grupo con un curso inexistente', function (): void {
    $handler = new CreateGroupHandler(new InMemoryGroupRepositoryForCreate, new InMemoryCourseRepositoryForGroups([]));

    $handler->handle(new CreateGroupCommand(
        courseId: '01981a64-8300-7b1d-b442-764ea7f92112',
        organizationId: null,
        name: 'Grupo A',
        teacherId: null,
        startsAt: '2026-01-15T00:00:00+00:00',
        endsAt: '2026-06-15T00:00:00+00:00',
    ));
})->throws(CourseNotFound::class);
