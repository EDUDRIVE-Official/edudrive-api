<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\ContentBlockFactory;
use Modules\Academic\Domain\Services\CourseCurriculumUnlockCalculator;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseModuleId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\LessonId;

uses(RefreshDatabase::class);

/**
 * Builds a course with 2 modules, 1 unit each, and publishes exactly one
 * lesson per unit. Module 2 requires module 1; unit 2 (in module 2) also
 * requires unit 1 (in module 1) directly, so both prerequisite layers are
 * exercised independently.
 *
 * @return array{course: Course, unit1: CourseUnitId, unit2: CourseUnitId, lesson1: LessonId, lesson2: LessonId}
 */
function courseWithTwoGatedModules(): array
{
    $module1Id = CourseModuleId::fromString((string) Str::uuid());
    $unit1Id = CourseUnitId::fromString((string) Str::uuid());
    $module2Id = CourseModuleId::fromString((string) Str::uuid());
    $unit2Id = CourseUnitId::fromString((string) Str::uuid());

    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('PRG-GATE-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso con prerrequisitos'),
    );

    $course->replaceCurriculum([
        CourseModule::create(
            id: $module1Id,
            code: CurriculumCode::fromString('MOD-01'),
            title: 'Modulo 1',
            description: 'Primer modulo.',
            objectives: null,
            durationMinutes: 30,
            position: 1,
            prerequisiteModuleIds: [],
            units: [
                CourseUnit::create(
                    id: $unit1Id,
                    code: CurriculumCode::fromString('UNI-01'),
                    title: 'Unidad 1',
                    description: 'Primera unidad.',
                    objectives: null,
                    durationMinutes: 15,
                    position: 1,
                    prerequisiteUnitIds: [],
                ),
            ],
        ),
        CourseModule::create(
            id: $module2Id,
            code: CurriculumCode::fromString('MOD-02'),
            title: 'Modulo 2',
            description: 'Segundo modulo.',
            objectives: null,
            durationMinutes: 30,
            position: 2,
            prerequisiteModuleIds: [$module1Id],
            units: [
                CourseUnit::create(
                    id: $unit2Id,
                    code: CurriculumCode::fromString('UNI-02'),
                    title: 'Unidad 2',
                    description: 'Segunda unidad.',
                    objectives: null,
                    durationMinutes: 15,
                    position: 1,
                    prerequisiteUnitIds: [$unit1Id],
                ),
            ],
        ),
    ]);

    app(CourseRepository::class)->save($course);

    $lesson1Id = LessonId::fromString((string) Str::uuid());
    $lesson2Id = LessonId::fromString((string) Str::uuid());
    $contents = app(UnitContentRepository::class);

    $contents->replaceAtomically($course->id(), $unit1Id, UnitContent::create($unit1Id, [
        Lesson::create($lesson1Id, CurriculumCode::fromString('LEC-01'), 'Leccion 1', null, 10, 1, [
            ContentBlockFactory::create(ContentBlockId::fromString((string) Str::uuid()), 'text', 1, ['markdown' => 'Contenido 1.']),
        ]),
    ]));

    $contents->replaceAtomically($course->id(), $unit2Id, UnitContent::create($unit2Id, [
        Lesson::create($lesson2Id, CurriculumCode::fromString('LEC-02'), 'Leccion 2', null, 10, 1, [
            ContentBlockFactory::create(ContentBlockId::fromString((string) Str::uuid()), 'text', 1, ['markdown' => 'Contenido 2.']),
        ]),
    ]));

    return ['course' => $course, 'unit1' => $unit1Id, 'unit2' => $unit2Id, 'lesson1' => $lesson1Id, 'lesson2' => $lesson2Id];
}

it('desbloquea el primer modulo y su unidad sin prerrequisitos', function (): void {
    $fixture = courseWithTwoGatedModules();
    $progress = EnrollmentProgress::create(EnrollmentId::fromString((string) Str::uuid()));

    $status = (new CourseCurriculumUnlockCalculator(app(UnitContentRepository::class)))->statusFor($fixture['course'], $progress);

    expect($status->isUnitUnlocked($fixture['unit1']))->toBeTrue()
        ->and($status->isUnitUnlocked($fixture['unit2']))->toBeFalse();
});

it('desbloquea el segundo modulo solo cuando el primero esta completo', function (): void {
    $fixture = courseWithTwoGatedModules();
    $progress = EnrollmentProgress::create(EnrollmentId::fromString((string) Str::uuid()));
    $progress->completeLesson($fixture['lesson1'], new DateTimeImmutable('now'), null);

    $status = (new CourseCurriculumUnlockCalculator(app(UnitContentRepository::class)))->statusFor($fixture['course'], $progress);

    expect($status->isUnitUnlocked($fixture['unit1']))->toBeTrue()
        ->and($status->isUnitUnlocked($fixture['unit2']))->toBeTrue();
});

it('resuelve a que unidad pertenece una leccion', function (): void {
    $fixture = courseWithTwoGatedModules();
    $progress = EnrollmentProgress::create(EnrollmentId::fromString((string) Str::uuid()));

    $status = (new CourseCurriculumUnlockCalculator(app(UnitContentRepository::class)))->statusFor($fixture['course'], $progress);

    expect($status->unitIdForLesson($fixture['lesson1'])?->equals($fixture['unit1']))->toBeTrue()
        ->and($status->unitIdForLesson($fixture['lesson2'])?->equals($fixture['unit2']))->toBeTrue()
        ->and($status->unitIdForLesson(LessonId::fromString((string) Str::uuid())))->toBeNull();
});

it('considera completada una unidad sin lecciones publicadas', function (): void {
    $module1Id = CourseModuleId::fromString((string) Str::uuid());
    $unitId = CourseUnitId::fromString((string) Str::uuid());

    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('PRG-GATE-EMPTY-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso sin contenido'),
    );
    $course->replaceCurriculum([
        CourseModule::create(
            id: $module1Id,
            code: CurriculumCode::fromString('MOD-01'),
            title: 'Modulo 1',
            description: 'Modulo sin contenido.',
            objectives: null,
            durationMinutes: null,
            position: 1,
            prerequisiteModuleIds: [],
            units: [
                CourseUnit::create(
                    id: $unitId,
                    code: CurriculumCode::fromString('UNI-01'),
                    title: 'Unidad 1',
                    description: 'Unidad sin contenido.',
                    objectives: null,
                    durationMinutes: null,
                    position: 1,
                    prerequisiteUnitIds: [],
                ),
            ],
        ),
    ]);
    app(CourseRepository::class)->save($course);

    $progress = EnrollmentProgress::create(EnrollmentId::fromString((string) Str::uuid()));
    $status = (new CourseCurriculumUnlockCalculator(app(UnitContentRepository::class)))->statusFor($course, $progress);

    expect($status->modules[0]->units[0]->completed)->toBeTrue();
});
