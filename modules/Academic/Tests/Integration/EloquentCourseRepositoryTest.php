<?php

declare(strict_types=1);

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Application\Exceptions\CourseCurriculumIdConflict;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Enums\CourseModality;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\Exceptions\CourseContentCannotBeModified;
use Modules\Academic\Domain\Services\ContentBlockFactory;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseModuleId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;
use Modules\Academic\Domain\ValueObjects\LessonId;
use Modules\Academic\Domain\ValueObjects\UnitContentCoverage;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentCourseRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentUnitContentRepository;

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

function persistedSimpleContent(string $unitId, string $lessonId, string $blockId, string $markdown): UnitContent
{
    return UnitContent::create(CourseUnitId::fromString($unitId), [Lesson::create(
        LessonId::fromString($lessonId), CurriculumCode::fromString('LEC-01'), 'Leccion', null, 10, 1,
        [ContentBlockFactory::create(ContentBlockId::fromString($blockId), 'text', 1, ['markdown' => $markdown])],
    )]);
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

it('publica con cobertura real antes de replace y conserva contenido y estado publicado', function (): void {
    $repository = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $course = persistedCourse('01981a64-8300-7b1d-b442-764ea7f91800', 'ATOMIC-001');
    $originalModuleId = '01981a64-8300-7b1d-b442-764ea7f91801';
    $unitId = '01981a64-8300-7b1d-b442-764ea7f91802';
    $course->replaceCurriculum([
        persistedCourseModule($originalModuleId, 'MOD-OLD', 1, [
            persistedCourseUnit($unitId, 'UNI-OLD', 1),
        ]),
    ]);
    $repository->save($course);
    $original = persistedSimpleContent(
        $unitId,
        '01981a64-8300-7b1d-b442-764ea7f91803',
        '01981a64-8300-7b1d-b442-764ea7f91804',
        'Original',
    );
    $contents->replaceAtomically($course->id(), CourseUnitId::fromString($unitId), $original);

    $repository->updateAtomicallyWithContentCoverage(
        $course->id(),
        static function (Course $locked, UnitContentCoverage $coverage): void {
            $locked->publish(new DateTimeImmutable('2026-08-04T12:00:00+00:00'), $coverage);
        },
    );

    $changed = persistedSimpleContent(
        $unitId,
        $original->lessons()[0]->id()->value(),
        $original->lessons()[0]->blocks()[0]->id()->value(),
        'Cambio no permitido',
    );
    expect(fn () => $contents->replaceAtomically(
        $course->id(),
        CourseUnitId::fromString($unitId),
        $changed,
    ))->toThrow(CourseContentCannotBeModified::class);

    $stored = $repository->findById($course->id());
    expect($stored?->status())->toBe(CourseStatus::Published)
        ->and($stored?->modules()[0]->id()->value())->toBe($originalModuleId)
        ->and($contents->findForCourseUnit($course->id(), CourseUnitId::fromString($unitId))?->lessons()[0]->blocks()[0]->payload())
        ->toBe(['markdown' => 'Original']);
});

it('reemplaza contenido antes de publish y publica exactamente la version nueva desde cobertura DB', function (): void {
    $repository = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $course = persistedCourse('01981a64-8300-7b1d-b442-764ea7f91810', 'ATOMIC-002');
    $unitId = '01981a64-8300-7b1d-b442-764ea7f91812';
    $course->replaceCurriculum([
        persistedCourseModule('01981a64-8300-7b1d-b442-764ea7f91811', 'MOD-OLD', 1, [
            persistedCourseUnit($unitId, 'UNI-OLD', 1),
        ]),
    ]);
    $repository->save($course);
    $original = persistedSimpleContent($unitId, '01981a64-8300-7b1d-b442-764ea7f91813', '01981a64-8300-7b1d-b442-764ea7f91814', 'Anterior');
    $contents->replaceAtomically($course->id(), CourseUnitId::fromString($unitId), $original);
    $newContent = persistedSimpleContent(
        $unitId,
        $original->lessons()[0]->id()->value(),
        $original->lessons()[0]->blocks()[0]->id()->value(),
        'Contenido nuevo exacto',
    );
    $contents->replaceAtomically($course->id(), CourseUnitId::fromString($unitId), $newContent);
    $repository->updateAtomicallyWithContentCoverage(
        $course->id(),
        static function (Course $locked, UnitContentCoverage $coverage): void {
            $locked->publish(new DateTimeImmutable('2026-08-04T12:00:00+00:00'), $coverage);
        },
    );

    $stored = $repository->findById($course->id());
    expect($stored?->status())->toBe(CourseStatus::Published)
        ->and($contents->findForCourseUnit($course->id(), CourseUnitId::fromString($unitId))?->lessons()[0]->blocks()[0]->payload())
        ->toBe(['markdown' => 'Contenido nuevo exacto']);
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

it('no transfiere un modulo insertado por otro curso entre ownership check y sync', function (): void {
    $repository = app(EloquentCourseRepository::class);
    $firstCourse = persistedCourse('01981a64-8300-7b1d-b442-764ea7f91700', 'RACE-001');
    $otherCourse = persistedCourse('01981a64-8300-7b1d-b442-764ea7f91701', 'RACE-002');
    $racingModuleId = '01981a64-8300-7b1d-b442-764ea7f91702';
    $repository->save($firstCourse);
    $repository->save($otherCourse);

    $firstCourse->rename(CourseTitle::fromString('Titulo que debe revertirse por carrera'));
    $firstCourse->replaceCurriculum([
        persistedCourseModule($racingModuleId, 'MOD-RACE', 1, [
            persistedCourseUnit('01981a64-8300-7b1d-b442-764ea7f91703', 'UNI-RACE', 1),
        ]),
    ]);

    $injected = false;
    DB::listen(function (QueryExecuted $query) use (&$injected, $racingModuleId, $otherCourse): void {
        if ($injected
            || ! str_contains($query->sql, 'academic_course_modules')
            || ! str_contains($query->sql, 'exists')) {
            return;
        }

        $injected = true;
        DB::table('academic_course_modules')->insert([
            'id' => $racingModuleId,
            'course_id' => $otherCourse->id()->value(),
            'code' => 'MOD-OTHER-RACE',
            'title' => 'Modulo concurrente',
            'description' => 'Creado despues del ownership check.',
            'objectives' => null,
            'duration_minutes' => 30,
            'position' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    try {
        $repository->save($firstCourse);
        test()->fail('Se esperaba conflicto por alta concurrente del modulo.');
    } catch (CourseCurriculumIdConflict $exception) {
        expect($exception->errorCode())->toBe('COURSE_CURRICULUM_ID_CONFLICT');
    }

    $stored = $repository->findById($firstCourse->id());
    expect($injected)->toBeTrue()
        ->and($stored?->title()->value())->toBe('Curso RACE-001')
        ->and($stored?->modules())->toBe([]);
});

it('no transfiere una unidad insertada por otro curso entre ownership check y sync', function (): void {
    $repository = app(EloquentCourseRepository::class);
    $firstCourse = persistedCourse('01981a64-8300-7b1d-b442-764ea7f91710', 'RACE-003');
    $otherCourse = persistedCourse('01981a64-8300-7b1d-b442-764ea7f91711', 'RACE-004');
    $ownModuleId = '01981a64-8300-7b1d-b442-764ea7f91712';
    $otherModuleId = '01981a64-8300-7b1d-b442-764ea7f91713';
    $ownUnitId = '01981a64-8300-7b1d-b442-764ea7f91714';
    $racingUnitId = '01981a64-8300-7b1d-b442-764ea7f91715';
    $firstCourse->replaceCurriculum([
        persistedCourseModule($ownModuleId, 'MOD-OWN', 1, [persistedCourseUnit($ownUnitId, 'UNI-OWN', 1)]),
    ]);
    $otherCourse->replaceCurriculum([
        persistedCourseModule($otherModuleId, 'MOD-OTHER', 1, [
            persistedCourseUnit('01981a64-8300-7b1d-b442-764ea7f91716', 'UNI-OTHER', 1),
        ]),
    ]);
    $repository->save($firstCourse);
    $repository->save($otherCourse);

    $firstCourse->rename(CourseTitle::fromString('Titulo unitario que debe revertirse'));
    $firstCourse->replaceCurriculum([
        persistedCourseModule($ownModuleId, 'MOD-OWN', 1, [
            persistedCourseUnit($ownUnitId, 'UNI-OWN', 1),
            persistedCourseUnit($racingUnitId, 'UNI-RACE', 2),
        ]),
    ]);

    $injected = false;
    DB::listen(function (QueryExecuted $query) use (&$injected, $racingUnitId, $otherModuleId): void {
        if ($injected
            || ! str_contains($query->sql, 'academic_course_units')
            || ! str_contains($query->sql, 'join')) {
            return;
        }

        $injected = true;
        DB::table('academic_course_units')->insert([
            'id' => $racingUnitId,
            'module_id' => $otherModuleId,
            'code' => 'UNI-OTHER-RACE',
            'title' => 'Unidad concurrente',
            'description' => 'Creada despues del ownership check.',
            'objectives' => null,
            'duration_minutes' => 15,
            'position' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    try {
        $repository->save($firstCourse);
        test()->fail('Se esperaba conflicto por alta concurrente de la unidad.');
    } catch (CourseCurriculumIdConflict $exception) {
        expect($exception->statusCode())->toBe(409);
    }

    $stored = $repository->findById($firstCourse->id());
    expect($injected)->toBeTrue()
        ->and($stored?->title()->value())->toBe('Curso RACE-003')
        ->and($stored?->modules()[0]->units())->toHaveCount(1)
        ->and($stored?->modules()[0]->units()[0]->id()->value())->toBe($ownUnitId);
});

it('ordena prerrequisitos de unidad por modulo posicion unidad posicion y UUID', function (): void {
    $repository = app(EloquentCourseRepository::class);
    $course = persistedCourse('01981a64-8300-7b1d-b442-764ea7f91720', 'ORDER-001');
    $firstUnitId = '01981a64-8300-7b1d-b442-764ea7f91722';
    $secondUnitId = '01981a64-8300-7b1d-b442-764ea7f91721';
    $firstModuleId = '01981a64-8300-7b1d-b442-764ea7f91723';
    $secondModuleId = '01981a64-8300-7b1d-b442-764ea7f91724';
    $course->replaceCurriculum([
        persistedCourseModule($firstModuleId, 'MOD-01', 1, [
            persistedCourseUnit($secondUnitId, 'UNI-02', 1),
        ]),
        persistedCourseModule($secondModuleId, 'MOD-02', 2, [
            persistedCourseUnit($firstUnitId, 'UNI-01', 1),
        ]),
    ]);
    $repository->save($course);

    $course->replaceCurriculum([
        persistedCourseModule($firstModuleId, 'MOD-01', 1, [
            persistedCourseUnit($firstUnitId, 'UNI-01', 1),
        ]),
        persistedCourseModule($secondModuleId, 'MOD-02', 2, [
            persistedCourseUnit($secondUnitId, 'UNI-02', 1),
        ]),
        persistedCourseModule('01981a64-8300-7b1d-b442-764ea7f91725', 'MOD-03', 3, [
            persistedCourseUnit(
                '01981a64-8300-7b1d-b442-764ea7f91726',
                'UNI-03',
                1,
                [$secondUnitId, $firstUnitId],
            ),
        ]),
    ]);

    $repository->save($course);
    $stored = $repository->findById($course->id());
    $prerequisites = $stored?->modules()[2]->units()[0]->prerequisiteUnitIds() ?? [];

    expect(array_map(static fn (CourseUnitId $id): string => $id->value(), $prerequisites))
        ->toBe([$firstUnitId, $secondUnitId]);
});

it('intercambia codigos al reordenar sin recrear modulos ni unidades', function (): void {
    $repository = app(EloquentCourseRepository::class);
    $course = persistedCourse('01981a64-8300-7b1d-b442-764ea7f91730', 'SWAP-001');
    $firstModuleId = '01981a64-8300-7b1d-b442-764ea7f91731';
    $secondModuleId = '01981a64-8300-7b1d-b442-764ea7f91732';
    $firstUnitId = '01981a64-8300-7b1d-b442-764ea7f91733';
    $secondUnitId = '01981a64-8300-7b1d-b442-764ea7f91734';
    $course->replaceCurriculum([
        persistedCourseModule($firstModuleId, 'MOD-A', 1, [persistedCourseUnit($firstUnitId, 'UNI-A', 1)]),
        persistedCourseModule($secondModuleId, 'MOD-B', 2, [persistedCourseUnit($secondUnitId, 'UNI-B', 1)]),
    ]);
    $repository->save($course);

    $course->replaceCurriculum([
        persistedCourseModule($secondModuleId, 'MOD-A', 1, [persistedCourseUnit($secondUnitId, 'UNI-A', 1)]),
        persistedCourseModule($firstModuleId, 'MOD-B', 2, [persistedCourseUnit($firstUnitId, 'UNI-B', 1)]),
    ]);
    $repository->save($course);
    $stored = $repository->findById($course->id());

    expect($stored?->modules()[0]->id()->value())->toBe($secondModuleId)
        ->and($stored?->modules()[0]->code()->value())->toBe('MOD-A')
        ->and($stored?->modules()[0]->units()[0]->id()->value())->toBe($secondUnitId)
        ->and($stored?->modules()[0]->units()[0]->code()->value())->toBe('UNI-A');
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

it('impone duraciones curriculares positivas tambien en la base de datos', function (): void {
    $repository = app(EloquentCourseRepository::class);
    $course = persistedCourse('01981a64-8300-7b1d-b442-764ea7f91820', 'CHECK-001');
    $repository->save($course);

    $moduleRow = [
        'id' => '01981a64-8300-7b1d-b442-764ea7f91821',
        'course_id' => $course->id()->value(),
        'code' => 'MOD-CHECK',
        'title' => 'Modulo check',
        'description' => 'Modulo para comprobar la restriccion.',
        'objectives' => null,
        'duration_minutes' => 0,
        'position' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    expect(fn () => DB::table('academic_course_modules')->insert($moduleRow))
        ->toThrow(QueryException::class);
    $moduleRow['duration_minutes'] = -1;
    expect(fn () => DB::table('academic_course_modules')->insert($moduleRow))
        ->toThrow(QueryException::class);

    $moduleRow['duration_minutes'] = null;
    DB::table('academic_course_modules')->insert($moduleRow);
    DB::table('academic_course_modules')->where('id', $moduleRow['id'])->update(['duration_minutes' => 30]);

    $unitRow = [
        'id' => '01981a64-8300-7b1d-b442-764ea7f91822',
        'module_id' => $moduleRow['id'],
        'code' => 'UNI-CHECK',
        'title' => 'Unidad check',
        'description' => 'Unidad para comprobar la restriccion.',
        'objectives' => null,
        'duration_minutes' => 0,
        'position' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    expect(fn () => DB::table('academic_course_units')->insert($unitRow))
        ->toThrow(QueryException::class);
    $unitRow['duration_minutes'] = -1;
    expect(fn () => DB::table('academic_course_units')->insert($unitRow))
        ->toThrow(QueryException::class);

    $unitRow['duration_minutes'] = null;
    DB::table('academic_course_units')->insert($unitRow);
    DB::table('academic_course_units')->where('id', $unitRow['id'])->update(['duration_minutes' => 15]);

    expect(DB::table('academic_course_modules')->where('id', $moduleRow['id'])->value('duration_minutes'))->toBe(30)
        ->and(DB::table('academic_course_units')->where('id', $unitRow['id'])->value('duration_minutes'))->toBe(15);

    if (DB::getDriverName() === 'sqlite') {
        $definitions = DB::table('sqlite_master')
            ->whereIn('name', ['academic_course_modules', 'academic_course_units'])
            ->pluck('sql')
            ->map(static fn (mixed $sql): string => mb_strtolower((string) $sql))
            ->all();

        expect($definitions)->toHaveCount(2);

        foreach ($definitions as $definition) {
            expect($definition)->toContain('check')
                ->toContain('duration_minutes')
                ->toContain('> 0');
        }
    }
});

it('compila la restriccion de duracion con la gramatica PostgreSQL', function (): void {
    $migration = require base_path(
        'modules/Academic/Infrastructure/Persistence/Migrations/2026_08_03_000003_create_academic_course_curriculum_tables.php',
    );
    $reflection = new ReflectionClass($migration);
    $definition = $reflection->getReflectionConstant('DURATION_MINUTES_DEFINITION')?->getValue();
    expect($definition)->toBeString();

    $connection = new PostgresConnection(new PDO('sqlite::memory:'));
    $connection->setSchemaGrammar(new PostgresGrammar($connection));
    $blueprint = new Blueprint(
        $connection,
        'academic_course_modules',
        static function (Blueprint $table) use ($definition): void {
            $table->create();
            $table->rawColumn('duration_minutes', $definition)->nullable();
        },
    );
    $sql = mb_strtolower(implode(' ', $blueprint->toSql()));

    expect($sql)->toContain('duration_minutes')
        ->toContain('check (duration_minutes is null or duration_minutes > 0)')
        ->toContain(' null');
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
