<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Group;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\GroupRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\GroupId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

function persistedGroupTestCourse(): Course
{
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('COURSE-GRP-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso de prueba para grupos'),
    );
    app(CourseRepository::class)->save($course);

    return $course;
}

function persistedGroupTestTeacherId(): string
{
    $teacher = User::register(
        id: (string) Str::uuid(),
        name: 'Docente de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($teacher);

    return $teacher->id();
}

it('guarda y recupera un grupo por identificador', function (): void {
    /** @var TestCase $this */
    $course = persistedGroupTestCourse();
    $repository = app(GroupRepository::class);

    $group = Group::create(
        id: GroupId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        organizationId: null,
        name: 'Grupo A',
        teacherId: null,
        startsAt: new DateTimeImmutable('2026-01-15'),
        endsAt: new DateTimeImmutable('2026-06-15'),
    );

    $repository->save($group);
    $persisted = $repository->findById($group->id());

    expect($persisted)->not->toBeNull()
        ->and($persisted?->name())->toBe('Grupo A')
        ->and($persisted?->courseId()->equals($course->id()))->toBeTrue();
});

it('actualiza el docente asignado al guardar de nuevo', function (): void {
    /** @var TestCase $this */
    $course = persistedGroupTestCourse();
    $repository = app(GroupRepository::class);

    $group = Group::create(
        id: GroupId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        organizationId: null,
        name: 'Grupo A',
        teacherId: null,
        startsAt: new DateTimeImmutable('2026-01-15'),
        endsAt: new DateTimeImmutable('2026-06-15'),
    );
    $repository->save($group);

    $group->assignTeacher(persistedGroupTestTeacherId());
    $repository->save($group);

    expect($repository->findById($group->id())?->teacherId())->toBe($group->teacherId());
});

it('lista todos los grupos cuando no se filtra por curso', function (): void {
    /** @var TestCase $this */
    $course = persistedGroupTestCourse();
    $repository = app(GroupRepository::class);

    $repository->save(Group::create(
        id: GroupId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        organizationId: null,
        name: 'Grupo A',
        teacherId: null,
        startsAt: new DateTimeImmutable('2026-01-15'),
        endsAt: new DateTimeImmutable('2026-06-15'),
    ));

    expect(count($repository->all()))->toBeGreaterThanOrEqual(1);
});

it('filtra los grupos por curso', function (): void {
    /** @var TestCase $this */
    $courseA = persistedGroupTestCourse();
    $courseB = persistedGroupTestCourse();
    $repository = app(GroupRepository::class);

    $repository->save(Group::create(
        id: GroupId::fromString((string) Str::uuid()),
        courseId: $courseA->id(),
        organizationId: null,
        name: 'Grupo A',
        teacherId: null,
        startsAt: new DateTimeImmutable('2026-01-15'),
        endsAt: new DateTimeImmutable('2026-06-15'),
    ));
    $repository->save(Group::create(
        id: GroupId::fromString((string) Str::uuid()),
        courseId: $courseB->id(),
        organizationId: null,
        name: 'Grupo B',
        teacherId: null,
        startsAt: new DateTimeImmutable('2026-01-15'),
        endsAt: new DateTimeImmutable('2026-06-15'),
    ));

    $result = $repository->all($courseA->id());

    expect($result)->toHaveCount(1)
        ->and($result[0]->name())->toBe('Grupo A');
});

it('devuelve null cuando el grupo no existe', function (): void {
    /** @var TestCase $this */
    $repository = app(GroupRepository::class);

    expect($repository->findById(GroupId::fromString((string) Str::uuid())))->toBeNull();
});

it('filtra los grupos por docente asignado', function (): void {
    /** @var TestCase $this */
    $course = persistedGroupTestCourse();
    $teacherId = persistedGroupTestTeacherId();
    $repository = app(GroupRepository::class);

    $repository->save(Group::create(
        id: GroupId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        organizationId: null,
        name: 'Grupo con docente',
        teacherId: $teacherId,
        startsAt: new DateTimeImmutable('2026-01-15'),
        endsAt: new DateTimeImmutable('2026-06-15'),
    ));
    $repository->save(Group::create(
        id: GroupId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        organizationId: null,
        name: 'Grupo sin docente',
        teacherId: null,
        startsAt: new DateTimeImmutable('2026-01-15'),
        endsAt: new DateTimeImmutable('2026-06-15'),
    ));

    $result = $repository->all(teacherId: $teacherId);

    expect($result)->toHaveCount(1)
        ->and($result[0]->name())->toBe('Grupo con docente');
});
