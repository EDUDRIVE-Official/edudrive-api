<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\ContentBlockFactory;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseModuleId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;
use Modules\Academic\Domain\ValueObjects\LessonId;

it('enumera los ids de leccion de todas las unidades del curso', function (): void {
    $course = createDraftCourseForPublishing('PRG-CAT-01');
    $catalog = new CourseLessonCatalog(app(UnitContentRepository::class));

    expect($catalog->lessonIdsFor($course))->toHaveCount(1);
});

it('ignora unidades sin contenido publicado', function (): void {
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('PRG-CAT-02'),
        title: CourseTitle::fromString('Curso sin contenido'),
    );
    addMinimalCurriculum($course);
    app(CourseRepository::class)->save($course);

    $catalog = new CourseLessonCatalog(app(UnitContentRepository::class));

    expect($catalog->lessonIdsFor($course))->toBe([]);
});

it('aplana los ids de leccion de multiples modulos y unidades, omitiendo las que no tienen contenido', function (): void {
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('PRG-CAT-03'),
        title: CourseTitle::fromString('Curso con multiples modulos'),
    );

    $firstUnitWithContent = CourseUnit::create(
        id: CourseUnitId::fromString((string) Str::uuid()),
        code: CurriculumCode::fromString('UNI-01'),
        title: 'Introduccion',
        description: 'Unidad curricular minima de prueba.',
        objectives: null,
        durationMinutes: 15,
        position: 1,
        prerequisiteUnitIds: [],
    );

    $secondUnitWithContent = CourseUnit::create(
        id: CourseUnitId::fromString((string) Str::uuid()),
        code: CurriculumCode::fromString('UNI-02'),
        title: 'Avanzado',
        description: 'Segunda unidad curricular de prueba.',
        objectives: null,
        durationMinutes: 15,
        position: 1,
        prerequisiteUnitIds: [],
    );

    $unitWithoutContent = CourseUnit::create(
        id: CourseUnitId::fromString((string) Str::uuid()),
        code: CurriculumCode::fromString('UNI-03'),
        title: 'Sin contenido',
        description: 'Tercera unidad curricular de prueba.',
        objectives: null,
        durationMinutes: 15,
        position: 1,
        prerequisiteUnitIds: [],
    );

    $course->replaceCurriculum([
        CourseModule::create(
            id: CourseModuleId::fromString((string) Str::uuid()),
            code: CurriculumCode::fromString('MOD-01'),
            title: 'Fundamentos',
            description: 'Modulo curricular minimo de prueba.',
            objectives: null,
            durationMinutes: 30,
            position: 1,
            prerequisiteModuleIds: [],
            units: [$firstUnitWithContent],
        ),
        CourseModule::create(
            id: CourseModuleId::fromString((string) Str::uuid()),
            code: CurriculumCode::fromString('MOD-02'),
            title: 'Avanzado',
            description: 'Segundo modulo curricular de prueba.',
            objectives: null,
            durationMinutes: 30,
            position: 2,
            prerequisiteModuleIds: [],
            units: [$secondUnitWithContent],
        ),
        CourseModule::create(
            id: CourseModuleId::fromString((string) Str::uuid()),
            code: CurriculumCode::fromString('MOD-03'),
            title: 'Sin contenido',
            description: 'Tercer modulo curricular de prueba.',
            objectives: null,
            durationMinutes: 30,
            position: 3,
            prerequisiteModuleIds: [],
            units: [$unitWithoutContent],
        ),
    ]);

    app(CourseRepository::class)->save($course);

    $contents = app(UnitContentRepository::class);

    $firstLessonId = LessonId::fromString((string) Str::uuid());
    $contents->replaceAtomically(
        $course->id(),
        $firstUnitWithContent->id(),
        UnitContent::create($firstUnitWithContent->id(), [
            Lesson::create(
                $firstLessonId,
                CurriculumCode::fromString('LEC-01'),
                'Leccion de prueba 1',
                null,
                10,
                1,
                [ContentBlockFactory::create(
                    ContentBlockId::fromString((string) Str::uuid()),
                    'text',
                    1,
                    ['markdown' => 'Contenido accesible de prueba 1.'],
                )],
            ),
        ]),
    );

    $secondLessonId = LessonId::fromString((string) Str::uuid());
    $contents->replaceAtomically(
        $course->id(),
        $secondUnitWithContent->id(),
        UnitContent::create($secondUnitWithContent->id(), [
            Lesson::create(
                $secondLessonId,
                CurriculumCode::fromString('LEC-02'),
                'Leccion de prueba 2',
                null,
                10,
                1,
                [ContentBlockFactory::create(
                    ContentBlockId::fromString((string) Str::uuid()),
                    'text',
                    1,
                    ['markdown' => 'Contenido accesible de prueba 2.'],
                )],
            ),
        ]),
    );

    $catalog = new CourseLessonCatalog($contents);

    expect($catalog->lessonIdsFor($course))
        ->toEqualCanonicalizing([$firstLessonId->value(), $secondLessonId->value()]);
});
