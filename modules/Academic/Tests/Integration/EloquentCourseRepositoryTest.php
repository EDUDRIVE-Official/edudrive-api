<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Modules\Academic\Application\Exceptions\CourseCurriculumIdConflict;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Enums\CourseModality;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseModuleId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentCourseRepository;

/** @param list<string> $prerequisiteUnitIds */
function persistedCourseUnit(
    string $id,
    string $code,
    int $position,
    array $prerequisiteUnitIds = [],
): CourseUnit {
    return CourseUnit::create(
        id: CourseUnitId::fromString($id),
        code: CurriculumCode::fromString($code),
        title: "Unidad {$code}",
        description: "Descripcion de {$code}.",
        objectives: "Objetivos de {$code}.",
        durationMinutes: 30,
        position: $position,
        prerequisiteUnitIds: array_map(
            static fn (string $value): CourseUnitId => CourseUnitId::fromString($value),
            $prerequisiteUnitIds,
        ),
    );
}

/**
 * @param  list<CourseUnit>  $units
 * @param  list<string>  $prerequisiteModuleIds
 */
function persistedCourseModule(
    string $id,
    string $code,
    int $position,
    array $units,
    array $prerequisiteModuleIds = [],
): CourseModule {
    return CourseModule::create(
        id: CourseModuleId::fromString($id),
        code: CurriculumCode::fromString($code),
        title: "Modulo {$code}",
        description: "Descripcion de {$code}.",
        objectives: "Objetivos de {$code}.",
        durationMinutes: 90,
        position: $position,
        prerequisiteModuleIds: array_map(
            static fn (string $value): CourseModuleId => CourseModuleId::fromString($value),
            $prerequisiteModuleIds,
        ),
        units: $units,
    );
}

function persistedCourse(string $id, string $code): Course
{
    return Course::create(
        id: CourseId::fromString($id),
        code: CourseCode::fromString($code),
        title: CourseTitle::fromString("Curso {$code}"),
    );
}

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

it('reconstruye dos modulos con unidades y prerrequisitos en un round trip', function (): void {
    $repository = app(EloquentCourseRepository::class);
    $course = persistedCourse('01981a64-8300-7b1d-b442-764ea7f91600', 'CURR-001');
    $firstModuleId = '01981a64-8300-7b1d-b442-764ea7f91601';
    $secondModuleId = '01981a64-8300-7b1d-b442-764ea7f91602';
    $firstUnitId = '01981a64-8300-7b1d-b442-764ea7f91603';
    $secondUnitId = '01981a64-8300-7b1d-b442-764ea7f91604';

    $course->replaceCurriculum([
        persistedCourseModule($firstModuleId, 'MOD-01', 1, [
            persistedCourseUnit($firstUnitId, 'UNI-01', 1),
            persistedCourseUnit($secondUnitId, 'UNI-02', 2, [$firstUnitId]),
        ]),
        persistedCourseModule($secondModuleId, 'MOD-02', 2, [
            persistedCourseUnit('01981a64-8300-7b1d-b442-764ea7f91605', 'UNI-03', 1, [$secondUnitId]),
        ], [$firstModuleId]),
    ]);

    $repository->save($course);
    $stored = $repository->findByCode(CourseCode::fromString('curr-001'));

    expect($stored?->modules())->toHaveCount(2)
        ->and($stored?->modules()[0]->id()->value())->toBe($firstModuleId)
        ->and($stored?->modules()[0]->units())->toHaveCount(2)
        ->and($stored?->modules()[0]->units()[1]->prerequisiteUnitIds()[0]->value())->toBe($firstUnitId)
        ->and($stored?->modules()[1]->prerequisiteModuleIds()[0]->value())->toBe($firstModuleId)
        ->and($stored?->modules()[1]->units()[0]->prerequisiteUnitIds()[0]->value())->toBe($secondUnitId);
});

it('reordena modulos y unidades preservando sus UUID', function (): void {
    $repository = app(EloquentCourseRepository::class);
    $course = persistedCourse('01981a64-8300-7b1d-b442-764ea7f91610', 'CURR-002');
    $firstModuleId = '01981a64-8300-7b1d-b442-764ea7f91611';
    $secondModuleId = '01981a64-8300-7b1d-b442-764ea7f91612';
    $firstUnitId = '01981a64-8300-7b1d-b442-764ea7f91613';
    $secondUnitId = '01981a64-8300-7b1d-b442-764ea7f91614';
    $thirdUnitId = '01981a64-8300-7b1d-b442-764ea7f91615';
    $course->replaceCurriculum([
        persistedCourseModule($firstModuleId, 'MOD-A', 1, [
            persistedCourseUnit($firstUnitId, 'UNI-A', 1),
            persistedCourseUnit($secondUnitId, 'UNI-B', 2),
        ]),
        persistedCourseModule($secondModuleId, 'MOD-B', 2, [persistedCourseUnit($thirdUnitId, 'UNI-C', 1)]),
    ]);
    $repository->save($course);

    $course->replaceCurriculum([
        persistedCourseModule($secondModuleId, 'MOD-B', 1, [persistedCourseUnit($thirdUnitId, 'UNI-C', 1)]),
        persistedCourseModule($firstModuleId, 'MOD-A', 2, [
            persistedCourseUnit($secondUnitId, 'UNI-B', 1),
            persistedCourseUnit($firstUnitId, 'UNI-A', 2),
        ]),
    ]);
    $repository->save($course);

    $stored = $repository->findById($course->id());
    expect(array_map(static fn (CourseModule $module): string => $module->id()->value(), $stored?->modules() ?? []))
        ->toBe([$secondModuleId, $firstModuleId])
        ->and(array_map(static fn (CourseUnit $unit): string => $unit->id()->value(), $stored?->modules()[1]->units() ?? []))
        ->toBe([$secondUnitId, $firstUnitId]);
});

it('elimina nodos obsoletos y sus pivotes', function (): void {
    $repository = app(EloquentCourseRepository::class);
    $course = persistedCourse('01981a64-8300-7b1d-b442-764ea7f91620', 'CURR-003');
    $firstModuleId = '01981a64-8300-7b1d-b442-764ea7f91621';
    $secondModuleId = '01981a64-8300-7b1d-b442-764ea7f91622';
    $firstUnitId = '01981a64-8300-7b1d-b442-764ea7f91623';
    $secondUnitId = '01981a64-8300-7b1d-b442-764ea7f91624';
    $course->replaceCurriculum([
        persistedCourseModule($firstModuleId, 'MOD-01', 1, [
            persistedCourseUnit($firstUnitId, 'UNI-01', 1),
            persistedCourseUnit($secondUnitId, 'UNI-02', 2, [$firstUnitId]),
        ]),
        persistedCourseModule($secondModuleId, 'MOD-02', 2, [
            persistedCourseUnit('01981a64-8300-7b1d-b442-764ea7f91625', 'UNI-03', 1, [$secondUnitId]),
        ], [$firstModuleId]),
    ]);
    $repository->save($course);

    $course->replaceCurriculum([
        persistedCourseModule($firstModuleId, 'MOD-01', 1, [persistedCourseUnit($firstUnitId, 'UNI-01', 1)]),
    ]);
    $repository->save($course);

    expect(DB::table('academic_course_modules')->where('id', $secondModuleId)->exists())->toBeFalse()
        ->and(DB::table('academic_course_units')->where('id', $secondUnitId)->exists())->toBeFalse()
        ->and(DB::table('academic_module_prerequisites')->count())->toBe(0)
        ->and(DB::table('academic_unit_prerequisites')->count())->toBe(0);
});

it('hace rollback y reporta conflicto si un UUID pertenece a otro curso', function (): void {
    $repository = app(EloquentCourseRepository::class);
    $firstCourse = persistedCourse('01981a64-8300-7b1d-b442-764ea7f91630', 'CURR-004');
    $secondCourse = persistedCourse('01981a64-8300-7b1d-b442-764ea7f91631', 'CURR-005');
    $ownedModuleId = '01981a64-8300-7b1d-b442-764ea7f91632';
    $firstCourse->replaceCurriculum([
        persistedCourseModule('01981a64-8300-7b1d-b442-764ea7f91633', 'MOD-OWN', 1, [
            persistedCourseUnit('01981a64-8300-7b1d-b442-764ea7f91634', 'UNI-OWN', 1),
        ]),
    ]);
    $secondCourse->replaceCurriculum([
        persistedCourseModule($ownedModuleId, 'MOD-OTHER', 1, [
            persistedCourseUnit('01981a64-8300-7b1d-b442-764ea7f91635', 'UNI-OTHER', 1),
        ]),
    ]);
    $repository->save($firstCourse);
    $repository->save($secondCourse);

    $firstCourse->rename(CourseTitle::fromString('Titulo que debe revertirse'));
    $firstCourse->replaceCurriculum([
        persistedCourseModule($ownedModuleId, 'MOD-CONFLICT', 1, [
            persistedCourseUnit('01981a64-8300-7b1d-b442-764ea7f91636', 'UNI-CONFLICT', 1),
        ]),
    ]);

    try {
        $repository->save($firstCourse);
        test()->fail('Se esperaba un conflicto de identificador curricular.');
    } catch (CourseCurriculumIdConflict $exception) {
        expect($exception->errorCode())->toBe('COURSE_CURRICULUM_ID_CONFLICT')
            ->and($exception->statusCode())->toBe(409);
    }

    $stored = $repository->findById($firstCourse->id());
    expect($stored?->title()->value())->toBe('Curso CURR-004')
        ->and($stored?->modules()[0]->code()->value())->toBe('MOD-OWN');
});

it('restaura un curso publicado legado sin filas curriculares', function (): void {
    $repository = app(EloquentCourseRepository::class);
    $course = Course::restore(
        id: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f91640'),
        code: CourseCode::fromString('CURR-006'),
        title: CourseTitle::fromString('Curso legado'),
        description: null,
        objectives: null,
        prerequisites: null,
        modality: null,
        durationHours: null,
        status: CourseStatus::Published,
        publishedAt: new DateTimeImmutable('2026-08-03T12:00:00+00:00'),
        archivedAt: null,
    );

    $repository->save($course);
    $stored = $repository->findById($course->id());

    expect($stored?->status())->toBe(CourseStatus::Published)
        ->and($stored?->modules())->toBe([]);
});

it('carga all con consultas acotadas y reconstruccion curricular completa', function (): void {
    $repository = app(EloquentCourseRepository::class);
    foreach ([1, 2] as $index) {
        $course = persistedCourse("01981a64-8300-7b1d-b442-764ea7f9165{$index}", "CURR-10{$index}");
        $course->replaceCurriculum([
            persistedCourseModule("01981a64-8300-7b1d-b442-764ea7f9166{$index}", 'MOD-01', 1, [
                persistedCourseUnit("01981a64-8300-7b1d-b442-764ea7f9167{$index}", 'UNI-01', 1),
            ]),
        ]);
        $repository->save($course);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    $courses = $repository->all();
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($courses)->toHaveCount(2)
        ->and($courses[0]->modules())->toHaveCount(1)
        ->and($courses[1]->modules()[0]->units())->toHaveCount(1)
        ->and($queryCount)->toBeLessThanOrEqual(5);
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
