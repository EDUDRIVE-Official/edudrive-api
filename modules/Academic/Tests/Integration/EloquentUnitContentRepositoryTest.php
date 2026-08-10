<?php

declare(strict_types=1);

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Application\Exceptions\CourseContentIdConflict;
use Modules\Academic\Application\Exceptions\CourseUnitNotFound;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Entities\ContentBlocks\ContentBlock;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Exceptions\CourseContentCannotBeModified;
use Modules\Academic\Domain\Exceptions\CourseUnitContentRequired;
use Modules\Academic\Domain\Exceptions\InvalidContentBlock;
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

function contentCourse(
    string $courseId,
    string $code,
    string $unitId,
    string $moduleId = '01981a64-8300-7b1d-b442-764ea7f92001',
): Course {
    $course = Course::create(
        CourseId::fromString($courseId),
        CourseCode::fromString($code),
        CourseTitle::fromString("Curso {$code}"),
    );
    $course->replaceCurriculum([
        CourseModule::create(
            CourseModuleId::fromString($moduleId),
            CurriculumCode::fromString('MOD-01'),
            'Modulo',
            'Descripcion',
            null,
            60,
            1,
            [],
            [CourseUnit::create(
                CourseUnitId::fromString($unitId),
                CurriculumCode::fromString('UNI-01'),
                'Unidad',
                'Descripcion',
                null,
                30,
                1,
                [],
            )],
        ),
    ]);

    return $course;
}

/** @return list<ContentBlock> */
function sixBlocks(): array
{
    $payloads = [
        ['text', ['markdown' => '# Seguridad vial']],
        ['image', ['url' => 'https://cdn.example.test/image.png', 'alt' => 'Vehiculo detenido']],
        ['video', ['url' => 'https://cdn.example.test/video.mp4', 'captions_url' => 'https://cdn.example.test/video.vtt', 'transcript' => 'Transcripcion']],
        ['audio', ['url' => 'https://cdn.example.test/audio.mp3', 'transcript' => 'Transcripcion']],
        ['interactive', ['url' => 'https://learning.example.test/activity', 'accessible_text' => 'Alternativa textual']],
        ['download', ['url' => 'https://cdn.example.test/guide.pdf', 'display_name' => 'Guia', 'mime_type' => 'application/pdf']],
    ];

    return array_map(
        static fn (array $definition, int $index) => ContentBlockFactory::create(
            ContentBlockId::fromString(sprintf('01981a64-8300-7b1d-b442-764ea7f921%02d', $index + 1)),
            $definition[0],
            $index + 1,
            $definition[1],
        ),
        $payloads,
        array_keys($payloads),
    );
}

function completeUnitContent(string $unitId, string $lessonId = '01981a64-8300-7b1d-b442-764ea7f92201'): UnitContent
{
    return UnitContent::create(CourseUnitId::fromString($unitId), [
        Lesson::create(
            LessonId::fromString($lessonId),
            CurriculumCode::fromString('LEC-01'),
            'Leccion accesible',
            'Resumen',
            25,
            1,
            sixBlocks(),
        ),
    ]);
}

it('devuelve contenido vacio para una unidad existente sin fila', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = '01981a64-8300-7b1d-b442-764ea7f92010';
    $unitId = '01981a64-8300-7b1d-b442-764ea7f92011';
    $courses->save(contentCourse($courseId, 'CONTENT-01', $unitId));

    $content = $contents->findForCourseUnit(CourseId::fromString($courseId), CourseUnitId::fromString($unitId));

    expect($content)->not->toBeNull()
        ->and($content?->lessons())->toBe([]);
});

it('devuelve status y contenido como un unico snapshot autoritativo', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f92012');
    $unitId = CourseUnitId::fromString('01981a64-8300-7b1d-b442-764ea7f92013');
    $courses->save(contentCourse($courseId->value(), 'CONTENT-SNAPSHOT', $unitId->value()));
    $courses->updateAtomically($courseId, static fn (Course $course) => $course->archive(new DateTimeImmutable));

    $snapshot = $contents->findSnapshotForCourseUnit($courseId, $unitId);

    expect($snapshot)->not->toBeNull()
        ->and($snapshot?->courseStatus()->value)->toBe('archived')
        ->and($snapshot?->content()->unitId()->value())->toBe($unitId->value())
        ->and($snapshot?->content()->lessons())->toBe([]);
});

it('persiste y restaura bloques tipados en orden mediante payload canonico', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = '01981a64-8300-7b1d-b442-764ea7f92020';
    $unitId = '01981a64-8300-7b1d-b442-764ea7f92021';
    $courses->save(contentCourse($courseId, 'CONTENT-02', $unitId));

    $blocks = sixBlocks();
    $roundTrip = UnitContent::create(CourseUnitId::fromString($unitId), [
        Lesson::create(
            LessonId::fromString('01981a64-8300-7b1d-b442-764ea7f92201'),
            CurriculumCode::fromString('LEC-01'),
            'Leccion recursos visuales',
            null,
            20,
            1,
            array_slice($blocks, 0, 3),
        ),
        Lesson::create(
            LessonId::fromString('01981a64-8300-7b1d-b442-764ea7f92202'),
            CurriculumCode::fromString('LEC-02'),
            'Leccion recursos auditivos',
            null,
            20,
            2,
            array_map(
                static fn ($block, int $index) => ContentBlockFactory::create(
                    $block->id(), $block->type(), $index + 1, $block->payload(),
                ),
                array_slice($blocks, 3),
                array_keys(array_slice($blocks, 3)),
            ),
        ),
    ]);

    $stored = $contents->replaceAtomically(
        CourseId::fromString($courseId),
        CourseUnitId::fromString($unitId),
        $roundTrip,
    );

    $restoredBlocks = array_merge(...array_map(static fn (Lesson $lesson): array => $lesson->blocks(), $stored?->lessons() ?? []));
    expect($stored?->lessons())->toHaveCount(2)
        ->and($restoredBlocks)->toHaveCount(6)
        ->and(array_map(static fn ($block): string => $block->type()->value, $restoredBlocks))
        ->toBe(['text', 'image', 'video', 'audio', 'interactive', 'download'])
        ->and($stored?->lessons()[0]->blocks()[1]->payload())
        ->toBe(['url' => 'https://cdn.example.test/image.png', 'alt' => 'Vehiculo detenido']);
});

it('impide transferir identificadores de leccion entre unidades', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $firstCourseId = '01981a64-8300-7b1d-b442-764ea7f92030';
    $firstUnitId = '01981a64-8300-7b1d-b442-764ea7f92031';
    $secondCourseId = '01981a64-8300-7b1d-b442-764ea7f92032';
    $secondUnitId = '01981a64-8300-7b1d-b442-764ea7f92033';
    $courses->save(contentCourse($firstCourseId, 'CONTENT-03', $firstUnitId));
    $courses->save(contentCourse(
        $secondCourseId,
        'CONTENT-04',
        $secondUnitId,
        '01981a64-8300-7b1d-b442-764ea7f92034',
    ));
    $contents->replaceAtomically(CourseId::fromString($firstCourseId), CourseUnitId::fromString($firstUnitId), completeUnitContent($firstUnitId));

    expect(fn () => $contents->replaceAtomically(
        CourseId::fromString($secondCourseId),
        CourseUnitId::fromString($secondUnitId),
        completeUnitContent($secondUnitId),
    ))->toThrow(CourseContentIdConflict::class);
});

it('rechaza unidad ajena sin revelar ownership y curso publicado sin mutar', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = '01981a64-8300-7b1d-b442-764ea7f92040';
    $unitId = '01981a64-8300-7b1d-b442-764ea7f92041';
    $courses->save(contentCourse($courseId, 'CONTENT-05', $unitId));

    expect(fn () => $contents->findForCourseUnit(
        CourseId::fromString($courseId),
        CourseUnitId::fromString('01981a64-8300-7b1d-b442-764ea7f92999'),
    ))->toThrow(CourseUnitNotFound::class);

    $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($unitId), completeUnitContent($unitId));
    $courses->updateAtomicallyWithContentCoverage(
        CourseId::fromString($courseId),
        static function (Course $course, UnitContentCoverage $coverage): void {
            approveCourseForPublishing($course);
            $course->publish(new DateTimeImmutable('2026-08-05T12:00:00+00:00'), $coverage);
        },
    );

    expect(fn () => $contents->replaceAtomically(
        CourseId::fromString($courseId), CourseUnitId::fromString($unitId), completeUnitContent($unitId),
    ))->toThrow(CourseContentCannotBeModified::class);
});

it('impone duracion positiva y cascada en las tablas de contenido', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = '01981a64-8300-7b1d-b442-764ea7f92050';
    $unitId = '01981a64-8300-7b1d-b442-764ea7f92051';
    $courses->save(contentCourse($courseId, 'CONTENT-06', $unitId));
    $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($unitId), completeUnitContent($unitId));

    expect(fn () => DB::table('academic_lessons')->insert([
        'id' => '01981a64-8300-7b1d-b442-764ea7f92052', 'unit_id' => $unitId, 'code' => 'LEC', 'title' => 'Leccion',
        'summary' => null, 'duration_minutes' => 0, 'position' => 2, 'created_at' => now(), 'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    DB::table('academic_courses')->where('id', $courseId)->delete();
    expect(DB::table('academic_unit_contents')->where('unit_id', $unitId)->exists())->toBeFalse()
        ->and(DB::table('academic_lessons')->where('unit_id', $unitId)->exists())->toBeFalse()
        ->and(DB::table('academic_lesson_blocks')->exists())->toBeFalse();
});

it('reordena lecciones y elimina nodos obsoletos preservando UUID', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = '01981a64-8300-7b1d-b442-764ea7f92300';
    $unitId = '01981a64-8300-7b1d-b442-764ea7f92301';
    $courses->save(contentCourse($courseId, 'CONTENT-07', $unitId));
    $firstLessonId = '01981a64-8300-7b1d-b442-764ea7f92302';
    $secondLessonId = '01981a64-8300-7b1d-b442-764ea7f92303';
    $firstBlockId = '01981a64-8300-7b1d-b442-764ea7f92304';
    $secondBlockId = '01981a64-8300-7b1d-b442-764ea7f92305';
    $lesson = static fn (string $id, string $code, int $position, string $blockId): Lesson => Lesson::create(
        LessonId::fromString($id), CurriculumCode::fromString($code), "Leccion {$code}", null, null, $position,
        [ContentBlockFactory::create(ContentBlockId::fromString($blockId), 'text', 1, ['markdown' => $code])],
    );
    $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($unitId), UnitContent::create(
        CourseUnitId::fromString($unitId),
        [$lesson($firstLessonId, 'LEC-A', 1, $firstBlockId), $lesson($secondLessonId, 'LEC-B', 2, $secondBlockId)],
    ));

    $stored = $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($unitId), UnitContent::create(
        CourseUnitId::fromString($unitId),
        [$lesson($secondLessonId, 'LEC-A', 1, $secondBlockId)],
    ));

    expect($stored?->lessons())->toHaveCount(1)
        ->and($stored?->lessons()[0]->id()->value())->toBe($secondLessonId)
        ->and($stored?->lessons()[0]->code()->value())->toBe('LEC-A')
        ->and(DB::table('academic_lessons')->where('id', $firstLessonId)->exists())->toBeFalse()
        ->and(DB::table('academic_lesson_blocks')->where('id', $firstBlockId)->exists())->toBeFalse();
});

it('calcula cobertura parcial y completa bajo la publicacion atomica', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = '01981a64-8300-7b1d-b442-764ea7f92400';
    $firstUnitId = '01981a64-8300-7b1d-b442-764ea7f92401';
    $secondUnitId = '01981a64-8300-7b1d-b442-764ea7f92402';
    $course = contentCourse($courseId, 'CONTENT-08', $firstUnitId);
    $module = $course->modules()[0];
    $course->replaceCurriculum([CourseModule::create(
        $module->id(), $module->code(), $module->title(), $module->description(), $module->objectives(),
        $module->durationMinutes(), 1, [], [
            $module->units()[0],
            CourseUnit::create(CourseUnitId::fromString($secondUnitId), CurriculumCode::fromString('UNI-02'), 'Unidad 2', 'Descripcion', null, 20, 2, []),
        ],
    )]);
    $courses->save($course);
    $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($firstUnitId), completeUnitContent($firstUnitId));

    expect(fn () => $courses->updateAtomicallyWithContentCoverage(
        CourseId::fromString($courseId),
        static function (Course $locked, UnitContentCoverage $coverage): void {
            approveCourseForPublishing($locked);
            $locked->publish(new DateTimeImmutable, $coverage);
        },
    ))->toThrow(CourseUnitContentRequired::class);
    expect($courses->findById(CourseId::fromString($courseId))?->status()->value)->toBe('draft');

    $secondContent = UnitContent::create(CourseUnitId::fromString($secondUnitId), [Lesson::create(
        LessonId::fromString('01981a64-8300-7b1d-b442-764ea7f92403'), CurriculumCode::fromString('LEC-02'),
        'Leccion 2', null, 10, 1, [ContentBlockFactory::create(
            ContentBlockId::fromString('01981a64-8300-7b1d-b442-764ea7f92404'), 'text', 1, ['markdown' => 'Completo'],
        )],
    )]);
    $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($secondUnitId), $secondContent);
    $published = $courses->updateAtomicallyWithContentCoverage(
        CourseId::fromString($courseId),
        static function (Course $locked, UnitContentCoverage $coverage): void {
            approveCourseForPublishing($locked);
            $locked->publish(new DateTimeImmutable, $coverage);
        },
    );

    expect($published?->status()->value)->toBe('published');
});

it('carga contenido con consultas acotadas y sin N mas uno', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = '01981a64-8300-7b1d-b442-764ea7f92500';
    $unitId = '01981a64-8300-7b1d-b442-764ea7f92501';
    $courses->save(contentCourse($courseId, 'CONTENT-09', $unitId));
    $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($unitId), completeUnitContent($unitId));
    DB::flushQueryLog();
    DB::enableQueryLog();
    $contents->findForCourseUnit(CourseId::fromString($courseId), CourseUnitId::fromString($unitId));
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queryCount)->toBeLessThanOrEqual(7);
});

it('compila el check de lecciones con la gramatica PostgreSQL', function (): void {
    $migration = require base_path('modules/Academic/Infrastructure/Persistence/Migrations/2026_08_05_000001_create_academic_unit_content_tables.php');
    $definition = (new ReflectionClass($migration))->getReflectionConstant('DURATION_MINUTES_DEFINITION')?->getValue();
    $connection = new PostgresConnection(new PDO('sqlite::memory:'));
    $connection->setSchemaGrammar(new PostgresGrammar($connection));
    $blueprint = new Blueprint($connection, 'academic_lessons', static function (Blueprint $table) use ($definition): void {
        $table->create();
        $table->rawColumn('duration_minutes', $definition)->nullable();
    });

    expect(mb_strtolower(implode(' ', $blueprint->toSql())))
        ->toContain('check (duration_minutes is null or duration_minutes > 0)');
});

it('devuelve null cuando el curso no existe en lectura y reemplazo', function (): void {
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f92600');
    $unitId = CourseUnitId::fromString('01981a64-8300-7b1d-b442-764ea7f92601');

    expect($contents->findForCourseUnit($courseId, $unitId))->toBeNull()
        ->and($contents->replaceAtomically($courseId, $unitId, UnitContent::create($unitId, [])))->toBeNull();
});

it('rechaza un agregado para otra unidad sin mutar contenido existente', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f92610');
    $unitId = CourseUnitId::fromString('01981a64-8300-7b1d-b442-764ea7f92611');
    $courses->save(contentCourse($courseId->value(), 'CONTENT-10', $unitId->value()));
    $previous = completeUnitContent($unitId->value());
    $contents->replaceAtomically($courseId, $unitId, $previous);

    expect(fn () => $contents->replaceAtomically(
        $courseId,
        $unitId,
        UnitContent::create(CourseUnitId::fromString('01981a64-8300-7b1d-b442-764ea7f92612'), []),
    ))->toThrow(CourseUnitNotFound::class);
    expect($contents->findForCourseUnit($courseId, $unitId)?->lessons()[0]->id()->value())
        ->toBe($previous->lessons()[0]->id()->value());
});

it('rechaza reemplazar contenido archivado y conserva las filas', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f92620');
    $unitId = CourseUnitId::fromString('01981a64-8300-7b1d-b442-764ea7f92621');
    $courses->save(contentCourse($courseId->value(), 'CONTENT-11', $unitId->value()));
    $previous = completeUnitContent($unitId->value());
    $contents->replaceAtomically($courseId, $unitId, $previous);
    $courses->updateAtomically($courseId, static fn (Course $course) => $course->archive(new DateTimeImmutable));

    expect(fn () => $contents->replaceAtomically($courseId, $unitId, $previous))
        ->toThrow(CourseContentCannotBeModified::class);
    expect(DB::table('academic_lessons')->where('unit_id', $unitId->value())->count())->toBe(1);
});

it('impide transferir un ContentBlockId global y conserva ambos contenidos', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $firstCourseId = '01981a64-8300-7b1d-b442-764ea7f92630';
    $firstUnitId = '01981a64-8300-7b1d-b442-764ea7f92631';
    $secondCourseId = '01981a64-8300-7b1d-b442-764ea7f92632';
    $secondUnitId = '01981a64-8300-7b1d-b442-764ea7f92633';
    $courses->save(contentCourse($firstCourseId, 'CONTENT-12', $firstUnitId));
    $courses->save(contentCourse($secondCourseId, 'CONTENT-13', $secondUnitId, '01981a64-8300-7b1d-b442-764ea7f92634'));
    $first = completeUnitContent($firstUnitId);
    $contents->replaceAtomically(CourseId::fromString($firstCourseId), CourseUnitId::fromString($firstUnitId), $first);
    $foreignBlock = $first->lessons()[0]->blocks()[0];
    $candidate = UnitContent::create(CourseUnitId::fromString($secondUnitId), [Lesson::create(
        LessonId::fromString('01981a64-8300-7b1d-b442-764ea7f92635'), CurriculumCode::fromString('LEC-X'),
        'Leccion X', null, null, 1, [ContentBlockFactory::create($foreignBlock->id(), 'text', 1, ['markdown' => 'Transferencia'])],
    )]);

    expect(fn () => $contents->replaceAtomically(
        CourseId::fromString($secondCourseId), CourseUnitId::fromString($secondUnitId), $candidate,
    ))->toThrow(CourseContentIdConflict::class);
    expect($contents->findForCourseUnit(CourseId::fromString($firstCourseId), CourseUnitId::fromString($firstUnitId))?->lessons()[0]->blocks()[0]->payload())
        ->toBe($foreignBlock->payload())
        ->and($contents->findForCourseUnit(CourseId::fromString($secondCourseId), CourseUnitId::fromString($secondUnitId))?->lessons())
        ->toBe([]);
});

it('simula en SQLite una inyeccion logica de LessonId y valida traduccion y rollback', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = '01981a64-8300-7b1d-b442-764ea7f92640';
    $unitId = '01981a64-8300-7b1d-b442-764ea7f92641';
    $competingUnitId = '01981a64-8300-7b1d-b442-764ea7f92644';
    $course = contentCourse($courseId, 'CONTENT-14', $unitId);
    $module = $course->modules()[0];
    $course->replaceCurriculum([CourseModule::create(
        $module->id(), $module->code(), $module->title(), $module->description(), $module->objectives(),
        $module->durationMinutes(), 1, [], [
            $module->units()[0],
            CourseUnit::create(CourseUnitId::fromString($competingUnitId), CurriculumCode::fromString('UNI-02'), 'Unidad competidora', 'Descripcion', null, 20, 2, []),
        ],
    )]);
    $courses->save($course);
    DB::table('academic_unit_contents')->insert(['unit_id' => $competingUnitId, 'created_at' => now(), 'updated_at' => now()]);
    $previous = completeUnitContent($unitId);
    $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($unitId), $previous);
    $racingId = '01981a64-8300-7b1d-b442-764ea7f92642';
    $candidate = UnitContent::create(CourseUnitId::fromString($unitId), [Lesson::create(
        LessonId::fromString($racingId), CurriculumCode::fromString('LEC-RACE'), 'Carrera', null, null, 1,
        [ContentBlockFactory::create(ContentBlockId::fromString('01981a64-8300-7b1d-b442-764ea7f92643'), 'text', 1, ['markdown' => 'Carrera'])],
    )]);
    $injected = false;
    // Inyeccion logica sobre la misma conexion SQLite; no pretende modelar MVCC PostgreSQL real.
    DB::listen(function (QueryExecuted $query) use (&$injected, $racingId, $competingUnitId): void {
        if ($injected || ! str_contains($query->sql, 'academic_lessons') || ! str_contains($query->sql, 'exists')) {
            return;
        }
        $injected = true;
        DB::table('academic_lessons')->insert([
            'id' => $racingId, 'unit_id' => $competingUnitId, 'code' => 'LEC-COMPETE', 'title' => 'Competidora',
            'summary' => null, 'duration_minutes' => null, 'position' => 2, 'created_at' => now(), 'updated_at' => now(),
        ]);
    });

    expect(fn () => $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($unitId), $candidate))
        ->toThrow(CourseContentIdConflict::class);
    expect($injected)->toBeTrue()
        ->and(DB::table('academic_lessons')->where('id', $racingId)->exists())->toBeFalse()
        ->and($contents->findForCourseUnit(CourseId::fromString($courseId), CourseUnitId::fromString($unitId))?->lessons()[0]->id()->value())
        ->toBe($previous->lessons()[0]->id()->value());
});

it('no traduce una QueryException del repositorio que no es PK global', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = '01981a64-8300-7b1d-b442-764ea7f92650';
    $unitId = '01981a64-8300-7b1d-b442-764ea7f92651';
    $courses->save(contentCourse($courseId, 'CONTENT-19', $unitId));
    $previous = completeUnitContent($unitId);
    $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($unitId), $previous);
    $candidate = UnitContent::create(CourseUnitId::fromString($unitId), [Lesson::create(
        LessonId::fromString('01981a64-8300-7b1d-b442-764ea7f92652'), CurriculumCode::fromString('LEC-RACE'),
        'Carrera codigo', null, null, 1,
        [ContentBlockFactory::create(ContentBlockId::fromString('01981a64-8300-7b1d-b442-764ea7f92653'), 'text', 1, ['markdown' => 'Carrera'])],
    )]);
    DB::statement(<<<'SQL'
        CREATE TRIGGER reject_non_pk_content_error
        BEFORE UPDATE OF code ON academic_lessons
        WHEN NEW.code = 'LEC-RACE'
        BEGIN
            SELECT RAISE(ABORT, 'forced non-pk content error');
        END
        SQL);

    expect(fn () => $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($unitId), $candidate))
        ->toThrow(QueryException::class);
    expect(DB::table('academic_lessons')->where('code', 'LEC-RACE')->exists())->toBeFalse()
        ->and($contents->findForCourseUnit(CourseId::fromString($courseId), CourseUnitId::fromString($unitId))?->lessons()[0]->id()->value())
        ->toBe($previous->lessons()[0]->id()->value());
});

it('intercambia realmente codigos y posiciones de dos UUID persistentes', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = '01981a64-8300-7b1d-b442-764ea7f92700';
    $unitId = '01981a64-8300-7b1d-b442-764ea7f92701';
    $courses->save(contentCourse($courseId, 'CONTENT-15', $unitId));
    $make = static fn (string $id, string $code, int $position, string $blockId): Lesson => Lesson::create(
        LessonId::fromString($id), CurriculumCode::fromString($code), $code, null, null, $position,
        [ContentBlockFactory::create(ContentBlockId::fromString($blockId), 'text', 1, ['markdown' => $code])],
    );
    $firstId = '01981a64-8300-7b1d-b442-764ea7f92702';
    $secondId = '01981a64-8300-7b1d-b442-764ea7f92703';
    $firstBlock = '01981a64-8300-7b1d-b442-764ea7f92704';
    $secondBlock = '01981a64-8300-7b1d-b442-764ea7f92705';
    $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($unitId), UnitContent::create(
        CourseUnitId::fromString($unitId), [$make($firstId, 'LEC-A', 1, $firstBlock), $make($secondId, 'LEC-B', 2, $secondBlock)],
    ));

    $swapped = $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($unitId), UnitContent::create(
        CourseUnitId::fromString($unitId), [$make($secondId, 'LEC-A', 1, $secondBlock), $make($firstId, 'LEC-B', 2, $firstBlock)],
    ));

    expect(array_map(static fn (Lesson $lesson): string => $lesson->id()->value(), $swapped?->lessons() ?? []))->toBe([$secondId, $firstId])
        ->and(array_map(static fn (Lesson $lesson): string => $lesson->code()->value(), $swapped?->lessons() ?? []))->toBe(['LEC-A', 'LEC-B'])
        ->and(DB::table('academic_lessons')->whereIn('id', [$firstId, $secondId])->count())->toBe(2);
});

it('propaga payload persistido corrupto y revierte un reemplazo fallido conservando el previo', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = '01981a64-8300-7b1d-b442-764ea7f92710';
    $unitId = '01981a64-8300-7b1d-b442-764ea7f92711';
    $courses->save(contentCourse($courseId, 'CONTENT-16', $unitId));
    $previous = completeUnitContent($unitId);
    $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($unitId), $previous);
    $blockId = $previous->lessons()[0]->blocks()[0]->id()->value();
    $candidate = UnitContent::create(CourseUnitId::fromString($unitId), [Lesson::create(
        $previous->lessons()[0]->id(), CurriculumCode::fromString('LEC-NEW'), 'Nueva', null, null, 1,
        [ContentBlockFactory::create(ContentBlockId::fromString($blockId), 'text', 1, ['markdown' => 'Nuevo'])],
    )]);
    DB::statement(sprintf(<<<'SQL'
        CREATE TRIGGER corrupt_final_block_payload
        AFTER UPDATE OF position ON academic_lesson_blocks
        WHEN NEW.id = '%s' AND NEW.position = 1
        BEGIN
            UPDATE academic_lesson_blocks SET payload = '{"markdown":""}' WHERE id = NEW.id;
        END
        SQL, $blockId));

    expect(fn () => $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($unitId), $candidate))
        ->toThrow(InvalidContentBlock::class);
    expect($contents->findForCourseUnit(CourseId::fromString($courseId), CourseUnitId::fromString($unitId))?->lessons()[0]->code()->value())
        ->toBe('LEC-01')
        ->and(DB::table('academic_lesson_blocks')->where('lesson_id', $previous->lessons()[0]->id()->value())->count())->toBe(6);
});

it('no oculta corrupcion persistida al cargar un payload invalido', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = '01981a64-8300-7b1d-b442-764ea7f92712';
    $unitId = '01981a64-8300-7b1d-b442-764ea7f92713';
    $courses->save(contentCourse($courseId, 'CONTENT-18', $unitId));
    $content = completeUnitContent($unitId);
    $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($unitId), $content);
    DB::table('academic_lesson_blocks')->where('id', $content->lessons()[0]->blocks()[0]->id()->value())
        ->update(['payload' => json_encode(['markdown' => ''])]);

    expect(fn () => $contents->findForCourseUnit(CourseId::fromString($courseId), CourseUnitId::fromString($unitId)))
        ->toThrow(InvalidContentBlock::class);
});

it('impone FK y unicidad de codigo y posiciones sin traducir QueryException directas', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = '01981a64-8300-7b1d-b442-764ea7f92720';
    $unitId = '01981a64-8300-7b1d-b442-764ea7f92721';
    $courses->save(contentCourse($courseId, 'CONTENT-17', $unitId));
    $content = completeUnitContent($unitId);
    $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($unitId), $content);
    $base = ['summary' => null, 'duration_minutes' => null, 'created_at' => now(), 'updated_at' => now()];

    expect(fn () => DB::table('academic_lessons')->insert([
        ...$base, 'id' => '01981a64-8300-7b1d-b442-764ea7f92722', 'unit_id' => $unitId,
        'code' => 'LEC-01', 'title' => 'Duplicado codigo', 'position' => 2,
    ]))->toThrow(QueryException::class);
    expect(fn () => DB::table('academic_lessons')->insert([
        ...$base, 'id' => '01981a64-8300-7b1d-b442-764ea7f92723', 'unit_id' => $unitId,
        'code' => 'LEC-02', 'title' => 'Duplicado posicion', 'position' => 1,
    ]))->toThrow(QueryException::class);
    expect(fn () => DB::table('academic_lesson_blocks')->insert([
        'id' => '01981a64-8300-7b1d-b442-764ea7f92724', 'lesson_id' => $content->lessons()[0]->id()->value(),
        'type' => 'text', 'position' => 1, 'payload' => json_encode(['markdown' => 'Duplicado']), 'created_at' => now(), 'updated_at' => now(),
    ]))->toThrow(QueryException::class);
    expect(fn () => DB::table('academic_unit_contents')->insert([
        'unit_id' => '01981a64-8300-7b1d-b442-764ea7f92990', 'created_at' => now(), 'updated_at' => now(),
    ]))->toThrow(QueryException::class);
    expect(fn () => DB::table('academic_lesson_blocks')->insert([
        'id' => '01981a64-8300-7b1d-b442-764ea7f92725', 'lesson_id' => '01981a64-8300-7b1d-b442-764ea7f92991',
        'type' => 'text', 'position' => 1, 'payload' => json_encode(['markdown' => 'Sin padre']), 'created_at' => now(), 'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('reemplaza y reordena mil bloques con un presupuesto acotado de cuarenta queries', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $contents = app(EloquentUnitContentRepository::class);
    $courseId = '01981a64-8300-7b1d-b442-764ea7f92800';
    $unitId = '01981a64-8300-7b1d-b442-764ea7f92801';
    $courses->save(contentCourse($courseId, 'CONTENT-PERF', $unitId));
    $lessons = [];

    for ($lessonNumber = 1; $lessonNumber <= 5; $lessonNumber++) {
        $blocks = [];
        for ($blockNumber = 1; $blockNumber <= 200; $blockNumber++) {
            $sequence = (($lessonNumber - 1) * 200) + $blockNumber;
            $blocks[] = ContentBlockFactory::create(
                ContentBlockId::fromString(sprintf('01981a64-8300-7b1d-b442-%012d', 928010000 + $sequence)),
                'text',
                $blockNumber,
                ['markdown' => "Contenido {$sequence}"],
            );
        }
        $lessons[] = Lesson::create(
            LessonId::fromString(sprintf('01981a64-8300-7b1d-b442-%012d', 928000000 + $lessonNumber)),
            CurriculumCode::fromString(sprintf('LEC-%03d', $lessonNumber)),
            "Leccion {$lessonNumber}",
            null,
            30,
            $lessonNumber,
            $blocks,
        );
    }
    $content = UnitContent::create(CourseUnitId::fromString($unitId), $lessons);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $stored = $contents->replaceAtomically(CourseId::fromString($courseId), CourseUnitId::fromString($unitId), $content);
    $createQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    $reorderedLessons = [];
    foreach (array_reverse($lessons) as $index => $lesson) {
        $reorderedLessons[] = Lesson::create(
            $lesson->id(), $lesson->code(), $lesson->title(), $lesson->summary(), $lesson->durationMinutes(),
            $index + 1, $lesson->blocks(),
        );
    }
    DB::flushQueryLog();
    DB::enableQueryLog();
    $reordered = $contents->replaceAtomically(
        CourseId::fromString($courseId),
        CourseUnitId::fromString($unitId),
        UnitContent::create(CourseUnitId::fromString($unitId), $reorderedLessons),
    );
    $reorderQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($createQueries)->toBeLessThanOrEqual(40)
        ->and($reorderQueries)->toBeLessThanOrEqual(40)
        ->and($stored?->lessons())->toHaveCount(5)
        ->and(array_sum(array_map(static fn (Lesson $lesson): int => count($lesson->blocks()), $stored?->lessons() ?? [])))->toBe(1000)
        ->and($stored?->lessons()[0]->blocks()[0]->payload())->toBe(['markdown' => 'Contenido 1'])
        ->and($stored?->lessons()[4]->blocks()[199]->payload())->toBe(['markdown' => 'Contenido 1000'])
        ->and($reordered?->lessons()[0]->id()->value())->toBe($lessons[4]->id()->value());
});
