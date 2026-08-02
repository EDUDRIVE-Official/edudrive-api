<?php

declare(strict_types=1);

use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Enums\CourseModality;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentCourseRepository;

it('guarda y recupera un curso por identificador', function (): void {
    $repository = app(EloquentCourseRepository::class);

    $course = Course::create(
        id: CourseId::fromString(
            '01981a64-8300-7b1d-b442-764ea7f915c0',
        ),
        code: CourseCode::fromString('EDU-001'),
        title: CourseTitle::fromString(
            'Introducción a la seguridad vial',
        ),
        description: 'Curso introductorio.',
    );

    $repository->save($course);

    $storedCourse = $repository->findById($course->id());

    expect($storedCourse)
        ->not->toBeNull()
        ->and($storedCourse?->id()->equals($course->id()))
        ->toBeTrue()
        ->and($storedCourse?->code()->value())
        ->toBe('EDU-001')
        ->and($storedCourse?->title()->value())
        ->toBe('Introducción a la seguridad vial')
        ->and($storedCourse?->description())
        ->toBe('Curso introductorio.')
        ->and($storedCourse?->status())
        ->toBe(CourseStatus::Draft);
});

it('busca un curso por código', function (): void {
    $repository = app(EloquentCourseRepository::class);

    $course = Course::create(
        id: CourseId::fromString(
            '01981a64-8300-7b1d-b442-764ea7f915c1',
        ),
        code: CourseCode::fromString('EDU-002'),
        title: CourseTitle::fromString('Conducción responsable'),
    );

    $repository->save($course);

    $storedCourse = $repository->findByCode(
        CourseCode::fromString('edu-002'),
    );

    expect($storedCourse)
        ->not->toBeNull()
        ->and($storedCourse?->id()->equals($course->id()))
        ->toBeTrue();
});

it('confirma si un código de curso ya existe', function (): void {
    $repository = app(EloquentCourseRepository::class);

    $course = Course::create(
        id: CourseId::fromString(
            '01981a64-8300-7b1d-b442-764ea7f915c2',
        ),
        code: CourseCode::fromString('EDU-003'),
        title: CourseTitle::fromString('Movilidad segura'),
    );

    $repository->save($course);

    expect(
        $repository->existsByCode(
            CourseCode::fromString('EDU-003'),
        ),
    )->toBeTrue()
        ->and(
            $repository->existsByCode(
                CourseCode::fromString('EDU-999'),
            ),
        )
        ->toBeFalse();
});

it('guarda y recupera los campos nuevos de un curso (objetivos, requisitos, modalidad, duración)', function (): void {
    $repository = app(EloquentCourseRepository::class);

    $course = Course::create(
        id: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f915c3'),
        code: CourseCode::fromString('EDU-004'),
        title: CourseTitle::fromString('Manejo defensivo'),
        objectives: 'Aplicar técnicas de manejo defensivo.',
        prerequisites: 'Licencia de conducir vigente.',
        modality: CourseModality::Hybrid,
        durationHours: 15,
    );

    $repository->save($course);

    $storedCourse = $repository->findById($course->id());

    expect($storedCourse?->objectives())
        ->toBe('Aplicar técnicas de manejo defensivo.')
        ->and($storedCourse?->prerequisites())
        ->toBe('Licencia de conducir vigente.')
        ->and($storedCourse?->modality())
        ->toBe(CourseModality::Hybrid)
        ->and($storedCourse?->durationHours())
        ->toBe(15);
});
