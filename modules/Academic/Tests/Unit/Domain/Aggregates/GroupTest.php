<?php

declare(strict_types=1);

use Modules\Academic\Domain\Aggregates\Group;
use Modules\Academic\Domain\Exceptions\InvalidGroupPeriod;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\GroupId;

function testGroupId(): GroupId
{
    return GroupId::fromString('01981a64-8300-7b1d-b442-764ea7f92111');
}

function testGroupCourseId(): CourseId
{
    return CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f92112');
}

it('crea un grupo con periodo lectivo valido', function (): void {
    $startsAt = new DateTimeImmutable('2026-01-15');
    $endsAt = new DateTimeImmutable('2026-06-15');

    $group = Group::create(
        id: testGroupId(),
        courseId: testGroupCourseId(),
        organizationId: 'org-1',
        name: 'Generación 2026-I',
        teacherId: 'teacher-1',
        startsAt: $startsAt,
        endsAt: $endsAt,
    );

    expect($group->id()->equals(testGroupId()))->toBeTrue()
        ->and($group->courseId()->equals(testGroupCourseId()))->toBeTrue()
        ->and($group->organizationId())->toBe('org-1')
        ->and($group->name())->toBe('Generación 2026-I')
        ->and($group->teacherId())->toBe('teacher-1')
        ->and($group->startsAt())->toEqual($startsAt)
        ->and($group->endsAt())->toEqual($endsAt);
});

it('crea un grupo sin docente ni organizacion asignados', function (): void {
    $group = Group::create(
        id: testGroupId(),
        courseId: testGroupCourseId(),
        organizationId: null,
        name: 'Grupo A',
        teacherId: null,
        startsAt: new DateTimeImmutable('2026-01-15'),
        endsAt: new DateTimeImmutable('2026-06-15'),
    );

    expect($group->organizationId())->toBeNull()
        ->and($group->teacherId())->toBeNull();
});

it('rechaza un nombre vacio', function (): void {
    Group::create(
        id: testGroupId(),
        courseId: testGroupCourseId(),
        organizationId: null,
        name: '   ',
        teacherId: null,
        startsAt: new DateTimeImmutable('2026-01-15'),
        endsAt: new DateTimeImmutable('2026-06-15'),
    );
})->throws(InvalidArgumentException::class);

it('rechaza un periodo lectivo donde el fin no es posterior al inicio', function (): void {
    Group::create(
        id: testGroupId(),
        courseId: testGroupCourseId(),
        organizationId: null,
        name: 'Grupo A',
        teacherId: null,
        startsAt: new DateTimeImmutable('2026-06-15'),
        endsAt: new DateTimeImmutable('2026-01-15'),
    );
})->throws(InvalidGroupPeriod::class);

it('rechaza un periodo lectivo donde el fin es igual al inicio', function (): void {
    $sameDate = new DateTimeImmutable('2026-01-15');

    Group::create(
        id: testGroupId(),
        courseId: testGroupCourseId(),
        organizationId: null,
        name: 'Grupo A',
        teacherId: null,
        startsAt: $sameDate,
        endsAt: $sameDate,
    );
})->throws(InvalidGroupPeriod::class);

it('reasigna el docente de un grupo', function (): void {
    $group = Group::create(
        id: testGroupId(),
        courseId: testGroupCourseId(),
        organizationId: null,
        name: 'Grupo A',
        teacherId: 'teacher-1',
        startsAt: new DateTimeImmutable('2026-01-15'),
        endsAt: new DateTimeImmutable('2026-06-15'),
    );

    $group->assignTeacher('teacher-2');

    expect($group->teacherId())->toBe('teacher-2');
});

it('permite quitar el docente asignado', function (): void {
    $group = Group::create(
        id: testGroupId(),
        courseId: testGroupCourseId(),
        organizationId: null,
        name: 'Grupo A',
        teacherId: 'teacher-1',
        startsAt: new DateTimeImmutable('2026-01-15'),
        endsAt: new DateTimeImmutable('2026-06-15'),
    );

    $group->assignTeacher(null);

    expect($group->teacherId())->toBeNull();
});

it('reconstruye un grupo desde persistencia', function (): void {
    $startsAt = new DateTimeImmutable('2026-01-15');
    $endsAt = new DateTimeImmutable('2026-06-15');

    $group = Group::restore(
        id: testGroupId(),
        courseId: testGroupCourseId(),
        organizationId: 'org-1',
        name: 'Grupo A',
        teacherId: 'teacher-1',
        startsAt: $startsAt,
        endsAt: $endsAt,
    );

    expect($group->name())->toBe('Grupo A')
        ->and($group->startsAt())->toEqual($startsAt);
});
