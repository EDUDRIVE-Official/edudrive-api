<?php

declare(strict_types=1);

use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Enums\CourseModality;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\Exceptions\ArchivedCourseCannotBeModified;
use Modules\Academic\Domain\Exceptions\CourseAlreadyArchived;
use Modules\Academic\Domain\Exceptions\CourseAlreadyPublished;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;

function createAcademicCourse(): Course
{
    return Course::create(
        id: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        code: CourseCode::fromString('EDU-001'),
        title: CourseTitle::fromString('Introducción a la seguridad vial'),
        description: 'Curso introductorio de EDUDRIVE.',
    );
}

it('crea un curso en estado borrador', function (): void {
    $course = createAcademicCourse();

    expect($course->id()->value())
        ->toBe('01981a64-8300-7b1d-b442-764ea7f915c0')
        ->and($course->code()->value())
        ->toBe('EDU-001')
        ->and($course->title()->value())
        ->toBe('Introducción a la seguridad vial')
        ->and($course->description())
        ->toBe('Curso introductorio de EDUDRIVE.')
        ->and($course->status())
        ->toBe(CourseStatus::Draft)
        ->and($course->publishedAt())
        ->toBeNull()
        ->and($course->archivedAt())
        ->toBeNull();
});

it('publica un curso', function (): void {
    $course = createAcademicCourse();
    $publishedAt = new DateTimeImmutable('2026-07-29 08:00:00');

    $course->publish($publishedAt);

    expect($course->status())
        ->toBe(CourseStatus::Published)
        ->and($course->publishedAt())
        ->toBe($publishedAt);
});

it('impide publicar dos veces el mismo curso', function (): void {
    $course = createAcademicCourse();

    $course->publish(new DateTimeImmutable('2026-07-29 08:00:00'));
    $course->publish(new DateTimeImmutable('2026-07-29 09:00:00'));
})->throws(
    CourseAlreadyPublished::class,
    'El curso ya está publicado.',
);

it('archiva un curso', function (): void {
    $course = createAcademicCourse();
    $archivedAt = new DateTimeImmutable('2026-07-29 10:00:00');

    $course->archive($archivedAt);

    expect($course->status())
        ->toBe(CourseStatus::Archived)
        ->and($course->archivedAt())
        ->toBe($archivedAt);
});

it('impide archivar dos veces el mismo curso', function (): void {
    $course = createAcademicCourse();

    $course->archive(new DateTimeImmutable('2026-07-29 10:00:00'));
    $course->archive(new DateTimeImmutable('2026-07-29 11:00:00'));
})->throws(
    CourseAlreadyArchived::class,
    'El curso ya está archivado.',
);

it('permite cambiar el título mientras el curso no esté archivado', function (): void {
    $course = createAcademicCourse();

    $course->rename(
        CourseTitle::fromString('Fundamentos de seguridad vial'),
    );

    expect($course->title()->value())
        ->toBe('Fundamentos de seguridad vial');
});

it('impide modificar un curso archivado', function (): void {
    $course = createAcademicCourse();

    $course->archive(new DateTimeImmutable('2026-07-29 10:00:00'));

    $course->rename(
        CourseTitle::fromString('Título no permitido'),
    );
})->throws(
    ArchivedCourseCannotBeModified::class,
    'Un curso archivado no puede ser modificado.',
);

it('normaliza una descripción vacía como nula', function (): void {
    $course = Course::create(
        id: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        code: CourseCode::fromString('EDU-001'),
        title: CourseTitle::fromString('Introducción a la seguridad vial'),
        description: '   ',
    );

    expect($course->description())->toBeNull();
});

it('crea un curso con objetivos, requisitos, modalidad y duración', function (): void {
    $course = Course::create(
        id: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        code: CourseCode::fromString('EDU-001'),
        title: CourseTitle::fromString('Introducción a la seguridad vial'),
        description: 'Curso introductorio de EDUDRIVE.',
        objectives: 'Comprender los principios básicos de seguridad vial.',
        prerequisites: 'Ninguno.',
        modality: CourseModality::Virtual,
        durationHours: 20,
    );

    expect($course->objectives())
        ->toBe('Comprender los principios básicos de seguridad vial.')
        ->and($course->prerequisites())
        ->toBe('Ninguno.')
        ->and($course->modality())
        ->toBe(CourseModality::Virtual)
        ->and($course->durationHours())
        ->toBe(20);
});

it('normaliza objetivos y requisitos vacíos como nulos', function (): void {
    $course = Course::create(
        id: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        code: CourseCode::fromString('EDU-001'),
        title: CourseTitle::fromString('Introducción a la seguridad vial'),
        objectives: '   ',
        prerequisites: '   ',
    );

    expect($course->objectives())->toBeNull()
        ->and($course->prerequisites())->toBeNull();
});

it('permite crear un curso sin los campos opcionales nuevos', function (): void {
    $course = createAcademicCourse();

    expect($course->objectives())->toBeNull()
        ->and($course->prerequisites())->toBeNull()
        ->and($course->modality())->toBeNull()
        ->and($course->durationHours())->toBeNull();
});
