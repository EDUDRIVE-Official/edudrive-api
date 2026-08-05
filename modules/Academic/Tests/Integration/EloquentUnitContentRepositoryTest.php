<?php

declare(strict_types=1);

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
        static fn (Course $course, UnitContentCoverage $coverage) => $course->publish(new DateTimeImmutable('2026-08-05T12:00:00+00:00'), $coverage),
    );

    expect(fn () => $contents->replaceAtomically(
        CourseId::fromString($courseId), CourseUnitId::fromString($unitId), completeUnitContent($unitId),
    ))->toThrow(CourseContentCannotBeModified::class);
});

it('impone duracion positiva y cascada en las tablas de contenido', function (): void {
    $courses = app(EloquentCourseRepository::class);
    $courseId = '01981a64-8300-7b1d-b442-764ea7f92050';
    $unitId = '01981a64-8300-7b1d-b442-764ea7f92051';
    $courses->save(contentCourse($courseId, 'CONTENT-06', $unitId));
    DB::table('academic_unit_contents')->insert(['unit_id' => $unitId, 'created_at' => now(), 'updated_at' => now()]);

    expect(fn () => DB::table('academic_lessons')->insert([
        'id' => '01981a64-8300-7b1d-b442-764ea7f92052', 'unit_id' => $unitId, 'code' => 'LEC', 'title' => 'Leccion',
        'summary' => null, 'duration_minutes' => 0, 'position' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    DB::table('academic_courses')->where('id', $courseId)->delete();
    expect(DB::table('academic_unit_contents')->where('unit_id', $unitId)->exists())->toBeFalse();
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
        static fn (Course $locked, UnitContentCoverage $coverage) => $locked->publish(new DateTimeImmutable, $coverage),
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
        static fn (Course $locked, UnitContentCoverage $coverage) => $locked->publish(new DateTimeImmutable, $coverage),
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
