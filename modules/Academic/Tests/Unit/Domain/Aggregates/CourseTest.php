<?php

declare(strict_types=1);

use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Enums\CourseModality;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\Exceptions\ArchivedCourseCannotBeModified;
use Modules\Academic\Domain\Exceptions\CourseAlreadyArchived;
use Modules\Academic\Domain\Exceptions\CourseAlreadyPublished;
use Modules\Academic\Domain\Exceptions\CourseCurriculumCannotBeModified;
use Modules\Academic\Domain\Exceptions\CourseCurriculumRequired;
use Modules\Academic\Domain\Exceptions\CourseModuleRequiresUnits;
use Modules\Academic\Domain\Exceptions\DuplicateCourseModule;
use Modules\Academic\Domain\Exceptions\DuplicateCourseUnit;
use Modules\Academic\Domain\Exceptions\InvalidCurriculumPosition;
use Modules\Academic\Domain\Exceptions\InvalidCurriculumPrerequisite;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseModuleId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;

function createAcademicCourse(): Course
{
    return Course::create(
        id: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        code: CourseCode::fromString('EDU-001'),
        title: CourseTitle::fromString('Introducción a la seguridad vial'),
        description: 'Curso introductorio de EDUDRIVE.',
    );
}

/** @param list<string> $prerequisiteIds */
function aggregateCourseUnit(
    string $id,
    string $code,
    int $position,
    array $prerequisiteIds = [],
): CourseUnit {
    return CourseUnit::create(
        id: CourseUnitId::fromString($id),
        code: CurriculumCode::fromString($code),
        title: "Unidad {$code}",
        description: "Descripcion {$code}",
        objectives: null,
        durationMinutes: 20,
        position: $position,
        prerequisiteUnitIds: array_map(
            static fn (string $prerequisiteId): CourseUnitId => CourseUnitId::fromString($prerequisiteId),
            $prerequisiteIds,
        ),
    );
}

/**
 * @param  list<CourseUnit>  $units
 * @param  list<string>  $prerequisiteIds
 */
function aggregateCourseModule(
    string $id,
    string $code,
    int $position,
    array $units,
    array $prerequisiteIds = [],
): CourseModule {
    return CourseModule::create(
        id: CourseModuleId::fromString($id),
        code: CurriculumCode::fromString($code),
        title: "Modulo {$code}",
        description: "Descripcion {$code}",
        objectives: null,
        durationMinutes: 60,
        position: $position,
        prerequisiteModuleIds: array_map(
            static fn (string $prerequisiteId): CourseModuleId => CourseModuleId::fromString($prerequisiteId),
            $prerequisiteIds,
        ),
        units: $units,
    );
}

/** @return list<CourseModule> */
function validAggregateCurriculum(): array
{
    $firstUnitId = '01981a64-8300-7b1d-b442-764ea7f91601';
    $secondUnitId = '01981a64-8300-7b1d-b442-764ea7f91602';
    $firstModuleId = '01981a64-8300-7b1d-b442-764ea7f91701';

    return [
        aggregateCourseModule(
            $firstModuleId,
            'MOD-01',
            1,
            [aggregateCourseUnit($firstUnitId, 'UNI-01', 1)],
        ),
        aggregateCourseModule(
            '01981a64-8300-7b1d-b442-764ea7f91702',
            'MOD-02',
            2,
            [aggregateCourseUnit($secondUnitId, 'UNI-01', 1, [$firstUnitId])],
            [$firstModuleId],
        ),
    ];
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
    $course->replaceCurriculum(validAggregateCurriculum());

    $course->publish($publishedAt);

    expect($course->status())
        ->toBe(CourseStatus::Published)
        ->and($course->publishedAt())
        ->toBe($publishedAt);
});

it('impide publicar dos veces el mismo curso', function (): void {
    $course = createAcademicCourse();
    $course->replaceCurriculum(validAggregateCurriculum());

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

it('inicia el curso sin modulos y reemplaza un curriculo valido de forma ordenada', function (): void {
    $course = createAcademicCourse();
    $curriculum = validAggregateCurriculum();

    expect($course->modules())->toBe([]);

    $course->replaceCurriculum($curriculum);

    expect($course->modules())->toBe($curriculum);
});

it('rechaza posiciones no consecutivas de modulos o unidades', function (array $curriculum): void {
    $course = createAcademicCourse();

    try {
        $course->replaceCurriculum($curriculum);

        test()->fail('Se esperaba InvalidCurriculumPosition.');
    } catch (InvalidCurriculumPosition $exception) {
        expect($exception->statusCode())->toBe(422)
            ->and($exception->errorCode())->toBe('INVALID_CURRICULUM_POSITION');
    }
})->with([
    'modulos' => fn (): array => [
        aggregateCourseModule(
            '01981a64-8300-7b1d-b442-764ea7f91701',
            'MOD-01',
            2,
            [aggregateCourseUnit('01981a64-8300-7b1d-b442-764ea7f91601', 'UNI-01', 1)],
        ),
    ],
    'unidades' => fn (): array => [
        aggregateCourseModule(
            '01981a64-8300-7b1d-b442-764ea7f91701',
            'MOD-01',
            1,
            [aggregateCourseUnit('01981a64-8300-7b1d-b442-764ea7f91601', 'UNI-01', 2)],
        ),
    ],
]);

it('rechaza uuid o codigo de modulo duplicado dentro del curso', function (string $secondId, string $secondCode): void {
    $course = createAcademicCourse();
    $unitOne = aggregateCourseUnit('01981a64-8300-7b1d-b442-764ea7f91601', 'UNI-01', 1);
    $unitTwo = aggregateCourseUnit('01981a64-8300-7b1d-b442-764ea7f91602', 'UNI-01', 1);

    try {
        $course->replaceCurriculum([
            aggregateCourseModule('01981a64-8300-7b1d-b442-764ea7f91701', 'MOD-01', 1, [$unitOne]),
            aggregateCourseModule($secondId, $secondCode, 2, [$unitTwo]),
        ]);

        test()->fail('Se esperaba DuplicateCourseModule.');
    } catch (DuplicateCourseModule $exception) {
        expect($exception->statusCode())->toBe(422)
            ->and($exception->errorCode())->toBe('DUPLICATE_COURSE_MODULE');
    }
})->with([
    'uuid duplicado' => ['01981a64-8300-7b1d-b442-764ea7f91701', 'MOD-02'],
    'codigo duplicado' => ['01981a64-8300-7b1d-b442-764ea7f91702', 'MOD-01'],
]);

it('rechaza uuid de unidad duplicado en el curso o codigo duplicado dentro del modulo', function (array $curriculum): void {
    $course = createAcademicCourse();

    try {
        $course->replaceCurriculum($curriculum);

        test()->fail('Se esperaba DuplicateCourseUnit.');
    } catch (DuplicateCourseUnit $exception) {
        expect($exception->statusCode())->toBe(422)
            ->and($exception->errorCode())->toBe('DUPLICATE_COURSE_UNIT');
    }
})->with([
    'uuid en modulos diferentes' => fn (): array => [
        aggregateCourseModule(
            '01981a64-8300-7b1d-b442-764ea7f91701',
            'MOD-01',
            1,
            [aggregateCourseUnit('01981a64-8300-7b1d-b442-764ea7f91601', 'UNI-01', 1)],
        ),
        aggregateCourseModule(
            '01981a64-8300-7b1d-b442-764ea7f91702',
            'MOD-02',
            2,
            [aggregateCourseUnit('01981a64-8300-7b1d-b442-764ea7f91601', 'UNI-01', 1)],
        ),
    ],
    'codigo en el mismo modulo' => fn (): array => [
        aggregateCourseModule(
            '01981a64-8300-7b1d-b442-764ea7f91701',
            'MOD-01',
            1,
            [
                aggregateCourseUnit('01981a64-8300-7b1d-b442-764ea7f91601', 'UNI-01', 1),
                aggregateCourseUnit('01981a64-8300-7b1d-b442-764ea7f91602', 'UNI-01', 2),
            ],
        ),
    ],
]);

it('rechaza prerrequisitos de modulo duplicados o que no hayan sido vistos', function (array $prerequisites): void {
    $course = createAcademicCourse();
    $firstModuleId = '01981a64-8300-7b1d-b442-764ea7f91701';
    $secondModuleId = '01981a64-8300-7b1d-b442-764ea7f91702';

    expect(fn () => $course->replaceCurriculum([
        aggregateCourseModule(
            $firstModuleId,
            'MOD-01',
            1,
            [aggregateCourseUnit('01981a64-8300-7b1d-b442-764ea7f91601', 'UNI-01', 1)],
            $prerequisites,
        ),
        aggregateCourseModule(
            $secondModuleId,
            'MOD-02',
            2,
            [aggregateCourseUnit('01981a64-8300-7b1d-b442-764ea7f91602', 'UNI-01', 1)],
        ),
    ]))->toThrow(InvalidCurriculumPrerequisite::class);
})->with([
    'duplicado' => [[
        '01981a64-8300-7b1d-b442-764ea7f91709',
        '01981a64-8300-7b1d-b442-764ea7f91709',
    ]],
    'propio' => [['01981a64-8300-7b1d-b442-764ea7f91701']],
    'futuro' => [['01981a64-8300-7b1d-b442-764ea7f91702']],
    'externo' => [['01981a64-8300-7b1d-b442-764ea7f91709']],
]);

it('rechaza prerrequisitos de unidad duplicados o que no hayan sido vistos', function (array $prerequisites): void {
    $course = createAcademicCourse();
    $firstUnitId = '01981a64-8300-7b1d-b442-764ea7f91601';
    $secondUnitId = '01981a64-8300-7b1d-b442-764ea7f91602';

    try {
        $course->replaceCurriculum([
            aggregateCourseModule(
                '01981a64-8300-7b1d-b442-764ea7f91701',
                'MOD-01',
                1,
                [
                    aggregateCourseUnit($firstUnitId, 'UNI-01', 1),
                    aggregateCourseUnit($secondUnitId, 'UNI-02', 2, $prerequisites),
                ],
            ),
        ]);

        test()->fail('Se esperaba InvalidCurriculumPrerequisite.');
    } catch (InvalidCurriculumPrerequisite $exception) {
        expect($exception->statusCode())->toBe(422)
            ->and($exception->errorCode())->toBe('INVALID_CURRICULUM_PREREQUISITE');
    }
})->with([
    'duplicado' => [[
        '01981a64-8300-7b1d-b442-764ea7f91601',
        '01981a64-8300-7b1d-b442-764ea7f91601',
    ]],
    'propio' => [['01981a64-8300-7b1d-b442-764ea7f91602']],
    'externo' => [['01981a64-8300-7b1d-b442-764ea7f91609']],
]);

it('conserva exactamente el curriculo anterior cuando el reemplazo candidato es invalido', function (): void {
    $course = createAcademicCourse();
    $original = validAggregateCurriculum();
    $course->replaceCurriculum($original);

    expect(fn () => $course->replaceCurriculum([
        aggregateCourseModule(
            '01981a64-8300-7b1d-b442-764ea7f91703',
            'MOD-03',
            2,
            [aggregateCourseUnit('01981a64-8300-7b1d-b442-764ea7f91603', 'UNI-01', 1)],
        ),
    ]))->toThrow(InvalidCurriculumPosition::class);

    expect($course->modules())->toBe($original);
});

it('exige modulos y al menos una unidad por modulo antes de publicar', function (array $curriculum, string $exceptionClass, string $errorCode): void {
    $course = createAcademicCourse();
    $course->replaceCurriculum($curriculum);

    try {
        $course->publish(new DateTimeImmutable('2026-08-03T12:00:00+00:00'));

        test()->fail("Se esperaba {$exceptionClass}.");
    } catch (Throwable $exception) {
        expect($exception)->toBeInstanceOf($exceptionClass)
            ->and($exception->statusCode())->toBe(422)
            ->and($exception->errorCode())->toBe($errorCode)
            ->and($course->status())->toBe(CourseStatus::Draft)
            ->and($course->publishedAt())->toBeNull();
    }
})->with([
    'sin modulos' => [[], CourseCurriculumRequired::class, 'COURSE_CURRICULUM_REQUIRED'],
    'modulo vacio' => [
        fn (): array => [aggregateCourseModule(
            '01981a64-8300-7b1d-b442-764ea7f91701',
            'MOD-01',
            1,
            [],
        )],
        CourseModuleRequiresUnits::class,
        'COURSE_MODULE_REQUIRES_UNITS',
    ],
]);

it('publica un curso con curriculo completo', function (): void {
    $course = createAcademicCourse();
    $course->replaceCurriculum(validAggregateCurriculum());

    $course->publish($publishedAt = new DateTimeImmutable('2026-08-03T12:00:00+00:00'));

    expect($course->status())->toBe(CourseStatus::Published)
        ->and($course->publishedAt())->toBe($publishedAt);
});

it('prioriza el ciclo de vida y rechaza reemplazar el curriculo publicado o archivado', function (CourseStatus $status): void {
    $course = createAcademicCourse();
    $curriculum = validAggregateCurriculum();
    $course->replaceCurriculum($curriculum);

    if ($status === CourseStatus::Published) {
        $course->publish(new DateTimeImmutable('2026-08-03T12:00:00+00:00'));
    } else {
        $course->archive(new DateTimeImmutable('2026-08-03T12:00:00+00:00'));
    }

    try {
        $course->replaceCurriculum([]);

        test()->fail('Se esperaba CourseCurriculumCannotBeModified.');
    } catch (CourseCurriculumCannotBeModified $exception) {
        expect($exception->statusCode())->toBe(422)
            ->and($exception->errorCode())->toBe('COURSE_CURRICULUM_CANNOT_BE_MODIFIED')
            ->and($course->modules())->toBe($curriculum);
    }
})->with([
    'publicado' => CourseStatus::Published,
    'archivado' => CourseStatus::Archived,
]);

it('restaura un curso publicado legado sin curriculo', function (): void {
    $course = Course::restore(
        id: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        code: CourseCode::fromString('EDU-001'),
        title: CourseTitle::fromString('Curso legado'),
        description: null,
        objectives: null,
        prerequisites: null,
        modality: null,
        durationHours: null,
        status: CourseStatus::Published,
        publishedAt: new DateTimeImmutable('2026-07-01T12:00:00+00:00'),
        archivedAt: null,
    );

    expect($course->status())->toBe(CourseStatus::Published)
        ->and($course->modules())->toBe([]);
});
