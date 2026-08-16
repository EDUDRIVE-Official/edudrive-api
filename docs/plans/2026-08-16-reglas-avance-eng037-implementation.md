# ENG-037 - Reglas de avance Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Bloquear y desbloquear módulos y unidades de un curso para un estudiante inscrito según prerrequisitos ya modelados en `Course`, exponiendo ese estado en una consulta dedicada y aplicándolo al intentar completar una lección de una unidad todavía bloqueada.

**Architecture:** Nuevo servicio de dominio `CourseCurriculumUnlockCalculator` que deriva (sin persistir) el estado de completitud/desbloqueo de cada módulo y unidad de un curso, combinando `Course` (prerrequisitos) con `EnrollmentProgress` (lecciones completadas, ENG-036). Se consume desde una nueva query HTTP de solo lectura y desde `CompleteLessonHandler` (ya existente), que ahora rechaza completar una lección si su unidad está bloqueada.

**Tech Stack:** Laravel 12, Pest, Sanctum, `CommandBus`/`QueryBus` propios, PHP 8.2+, `modules/Academic`.

**Diseño de referencia:** `docs/plans/2026-08-16-reglas-avance-eng037-design.md`

---

### Task 1: Value objects de desbloqueo y `CourseCurriculumUnlockCalculator`

**Files:**
- Create: `modules/Academic/Domain/ValueObjects/UnitUnlockStatus.php`
- Create: `modules/Academic/Domain/ValueObjects/ModuleUnlockStatus.php`
- Create: `modules/Academic/Domain/ValueObjects/CurriculumUnlockStatus.php`
- Create: `modules/Academic/Domain/Services/CourseCurriculumUnlockCalculator.php`
- Test: `modules/Academic/Tests/Unit/Domain/Services/CourseCurriculumUnlockCalculatorTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Entities\ContentBlocks\ContentBlockFactory;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
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
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/Services/CourseCurriculumUnlockCalculatorTest.php`

Expected: FAIL porque `CourseCurriculumUnlockCalculator` y los value objects no existen.

**Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

final readonly class UnitUnlockStatus
{
    /** @param list<string> $lessonIds */
    public function __construct(
        public CourseUnitId $unitId,
        public bool $completed,
        public bool $unlocked,
        private array $lessonIds,
    ) {}

    public function containsLesson(LessonId $lessonId): bool
    {
        return in_array($lessonId->value(), $this->lessonIds, true);
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

final readonly class ModuleUnlockStatus
{
    /** @param list<UnitUnlockStatus> $units */
    public function __construct(
        public CourseModuleId $moduleId,
        public bool $completed,
        public bool $unlocked,
        public array $units,
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

final readonly class CurriculumUnlockStatus
{
    /** @param list<ModuleUnlockStatus> $modules */
    public function __construct(public array $modules) {}

    public function isUnitUnlocked(CourseUnitId $unitId): bool
    {
        foreach ($this->modules as $module) {
            foreach ($module->units as $unit) {
                if ($unit->unitId->equals($unitId)) {
                    return $unit->unlocked;
                }
            }
        }

        return false;
    }

    public function unitIdForLesson(LessonId $lessonId): ?CourseUnitId
    {
        foreach ($this->modules as $module) {
            foreach ($module->units as $unit) {
                if ($unit->containsLesson($lessonId)) {
                    return $unit->unitId;
                }
            }
        }

        return null;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Services;

use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\ValueObjects\CurriculumUnlockStatus;
use Modules\Academic\Domain\ValueObjects\ModuleUnlockStatus;
use Modules\Academic\Domain\ValueObjects\UnitUnlockStatus;

final readonly class CourseCurriculumUnlockCalculator
{
    public function __construct(private UnitContentRepository $unitContents) {}

    public function statusFor(Course $course, EnrollmentProgress $progress): CurriculumUnlockStatus
    {
        $completedLessonIds = $progress->completedLessonIds();

        /** @var array<string, list<string>> $unitLessonIds */
        $unitLessonIds = [];
        /** @var array<string, bool> $unitCompleted */
        $unitCompleted = [];
        /** @var array<string, list<string>> $moduleUnitIds */
        $moduleUnitIds = [];

        foreach ($course->modules() as $module) {
            $unitIdsForModule = [];
            foreach ($module->units() as $unit) {
                $content = $this->unitContents->findForCourseUnit($course->id(), $unit->id());
                $lessonIds = [];
                if ($content !== null) {
                    foreach ($content->lessons() as $lesson) {
                        $lessonIds[] = $lesson->id()->value();
                    }
                }

                $unitLessonIds[$unit->id()->value()] = $lessonIds;
                $unitCompleted[$unit->id()->value()] = self::allPresent($lessonIds, $completedLessonIds);
                $unitIdsForModule[] = $unit->id()->value();
            }

            $moduleUnitIds[$module->id()->value()] = $unitIdsForModule;
        }

        /** @var array<string, bool> $moduleCompleted */
        $moduleCompleted = [];
        foreach ($moduleUnitIds as $moduleIdValue => $unitIds) {
            $moduleCompleted[$moduleIdValue] = self::allTrue($unitIds, $unitCompleted);
        }

        /** @var array<string, bool> $moduleUnlocked */
        $moduleUnlocked = [];
        foreach ($course->modules() as $module) {
            $unlocked = true;
            foreach ($module->prerequisiteModuleIds() as $prerequisiteModuleId) {
                if (! ($moduleCompleted[$prerequisiteModuleId->value()] ?? false)) {
                    $unlocked = false;
                    break;
                }
            }

            $moduleUnlocked[$module->id()->value()] = $unlocked;
        }

        $modules = [];
        foreach ($course->modules() as $module) {
            $units = [];
            foreach ($module->units() as $unit) {
                $unitPrerequisitesSatisfied = true;
                foreach ($unit->prerequisiteUnitIds() as $prerequisiteUnitId) {
                    if (! ($unitCompleted[$prerequisiteUnitId->value()] ?? false)) {
                        $unitPrerequisitesSatisfied = false;
                        break;
                    }
                }

                $units[] = new UnitUnlockStatus(
                    unitId: $unit->id(),
                    completed: $unitCompleted[$unit->id()->value()],
                    unlocked: $moduleUnlocked[$module->id()->value()] && $unitPrerequisitesSatisfied,
                    lessonIds: $unitLessonIds[$unit->id()->value()],
                );
            }

            $modules[] = new ModuleUnlockStatus(
                moduleId: $module->id(),
                completed: $moduleCompleted[$module->id()->value()],
                unlocked: $moduleUnlocked[$module->id()->value()],
                units: $units,
            );
        }

        return new CurriculumUnlockStatus($modules);
    }

    /**
     * @param  list<string>  $lessonIds
     * @param  list<string>  $completedLessonIds
     */
    private static function allPresent(array $lessonIds, array $completedLessonIds): bool
    {
        foreach ($lessonIds as $lessonId) {
            if (! in_array($lessonId, $completedLessonIds, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $ids
     * @param  array<string, bool>  $map
     */
    private static function allTrue(array $ids, array $map): bool
    {
        foreach ($ids as $id) {
            if (! ($map[$id] ?? false)) {
                return false;
            }
        }

        return true;
    }
}
```

**Step 4: Run test to verify it passes.** Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Domain/ValueObjects/UnitUnlockStatus.php modules/Academic/Domain/ValueObjects/ModuleUnlockStatus.php modules/Academic/Domain/ValueObjects/CurriculumUnlockStatus.php modules/Academic/Domain/Services/CourseCurriculumUnlockCalculator.php modules/Academic/Tests/Unit/Domain/Services/CourseCurriculumUnlockCalculatorTest.php
git commit -m "feat(academic): add curriculum unlock calculator"
```

---

### Task 2: Excepción `UnitLocked`

**Files:**
- Create: `modules/Academic/Application/Exceptions/UnitLocked.php`

**Step 1: Write the exception**

No requiere test propio (excepción simple, cubierta indirectamente por los tests de la Task 3), mismo patrón que `LessonNotFound` (ENG-036).

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class UnitLocked extends DomainException
{
    public static function withId(string $unitId): self
    {
        return new self(
            message: sprintf('La unidad %s todavia esta bloqueada por prerrequisitos pendientes.', $unitId),
            errorCode: 'UNIT_LOCKED',
            statusCode: 422,
        );
    }
}
```

**Step 2: Commit**

```bash
git add modules/Academic/Application/Exceptions/UnitLocked.php
git commit -m "feat(academic): add unit locked exception"
```

---

### Task 3: `CompleteLessonHandler` rechaza unidades bloqueadas

**Files:**
- Modify: `modules/Academic/Application/UseCases/CompleteLessonHandler.php`
- Modify: `modules/Academic/Tests/Unit/Application/CompleteLessonHandlerTest.php`

**Step 1: Write the failing test**

Agrega al archivo existente (no elimines los tests actuales), junto a sus `use` correspondientes (`CourseCurriculumUnlockCalculator`, `UnitLocked`, `CourseModule`, `CourseUnit`, `CurriculumCode`, `CourseModuleId`, `CourseUnitId`):

```php
it('rechaza completar una leccion de una unidad bloqueada por prerrequisitos', function (): void {
    $module1Id = \Modules\Academic\Domain\ValueObjects\CourseModuleId::fromString((string) Str::uuid());
    $unit1Id = \Modules\Academic\Domain\ValueObjects\CourseUnitId::fromString((string) Str::uuid());
    $module2Id = \Modules\Academic\Domain\ValueObjects\CourseModuleId::fromString((string) Str::uuid());
    $unit2Id = \Modules\Academic\Domain\ValueObjects\CourseUnitId::fromString((string) Str::uuid());

    $course = \Modules\Academic\Domain\Aggregates\Course::create(
        id: \Modules\Academic\Domain\ValueObjects\CourseId::fromString((string) Str::uuid()),
        code: \Modules\Academic\Domain\ValueObjects\CourseCode::fromString('PRG-CL-GATE-'.strtoupper((string) Str::random(4))),
        title: \Modules\Academic\Domain\ValueObjects\CourseTitle::fromString('Curso con prerrequisitos'),
    );
    $course->replaceCurriculum([
        \Modules\Academic\Domain\Entities\CourseModule::create(
            id: $module1Id,
            code: \Modules\Academic\Domain\ValueObjects\CurriculumCode::fromString('MOD-01'),
            title: 'Modulo 1',
            description: 'Primer modulo.',
            objectives: null,
            durationMinutes: 30,
            position: 1,
            prerequisiteModuleIds: [],
            units: [
                \Modules\Academic\Domain\Entities\CourseUnit::create(
                    id: $unit1Id,
                    code: \Modules\Academic\Domain\ValueObjects\CurriculumCode::fromString('UNI-01'),
                    title: 'Unidad 1',
                    description: 'Primera unidad.',
                    objectives: null,
                    durationMinutes: 15,
                    position: 1,
                    prerequisiteUnitIds: [],
                ),
            ],
        ),
        \Modules\Academic\Domain\Entities\CourseModule::create(
            id: $module2Id,
            code: \Modules\Academic\Domain\ValueObjects\CurriculumCode::fromString('MOD-02'),
            title: 'Modulo 2',
            description: 'Segundo modulo.',
            objectives: null,
            durationMinutes: 30,
            position: 2,
            prerequisiteModuleIds: [$module1Id],
            units: [
                \Modules\Academic\Domain\Entities\CourseUnit::create(
                    id: $unit2Id,
                    code: \Modules\Academic\Domain\ValueObjects\CurriculumCode::fromString('UNI-02'),
                    title: 'Unidad 2',
                    description: 'Segunda unidad.',
                    objectives: null,
                    durationMinutes: 15,
                    position: 1,
                    prerequisiteUnitIds: [],
                ),
            ],
        ),
    ]);
    app(CourseRepository::class)->save($course);

    $lesson2Id = LessonId::fromString((string) Str::uuid());
    app(UnitContentRepository::class)->replaceAtomically($course->id(), $unit2Id, \Modules\Academic\Domain\Aggregates\UnitContent::create($unit2Id, [
        \Modules\Academic\Domain\Entities\Lesson::create($lesson2Id, \Modules\Academic\Domain\ValueObjects\CurriculumCode::fromString('LEC-02'), 'Leccion 2', null, 10, 1, [
            \Modules\Academic\Domain\Entities\ContentBlocks\ContentBlockFactory::create(\Modules\Academic\Domain\ValueObjects\ContentBlockId::fromString((string) Str::uuid()), 'text', 1, ['markdown' => 'Contenido.']),
        ]),
    ]));

    $userId = persistedTaskSevenUserId();
    $enrollment = \Modules\Academic\Domain\Aggregates\Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: $userId,
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    expect(fn () => completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: $enrollment->id()->value(),
        lessonId: $lesson2Id->value(),
        userId: $userId,
        timeSpentMinutes: null,
    )))->toThrow(\Modules\Academic\Application\Exceptions\UnitLocked::class);
});
```

> **Nota:** este test construye el curso "a mano" (en vez de `createDraftCourseForPublishing()`) porque necesita 2 módulos con un prerrequisito real entre ellos; el helper compartido solo genera 1 módulo/1 unidad. Se usan namespaces completos inline para no inflar la lista de `use` del archivo — si prefieres, puedes convertirlos a imports al inicio del archivo, ambas formas son válidas en Pest.

**Step 2: Update `completeLessonHandler()` helper**

El helper existente en este mismo archivo (usado por todos los tests de esta suite) debe construir el handler con el nuevo colaborador:

```php
function completeLessonHandler(): CompleteLessonHandler
{
    return new CompleteLessonHandler(
        app(EnrollmentRepository::class),
        app(EnrollmentProgressRepository::class),
        app(CourseRepository::class),
        new CourseLessonCatalog(app(UnitContentRepository::class)),
        new CourseCurriculumUnlockCalculator(app(UnitContentRepository::class)),
        new EnrollmentProgressCalculator(
            app(CourseRepository::class),
            new CourseLessonCatalog(app(UnitContentRepository::class)),
            app(ExamRepository::class),
            app(ExamAttemptRepository::class),
        ),
    );
}
```

Agrega `use Modules\Academic\Domain\Services\CourseCurriculumUnlockCalculator;` al inicio del archivo.

**Step 3: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Application/CompleteLessonHandlerTest.php`

Expected: FAIL — el constructor de `CompleteLessonHandler` todavía no acepta el nuevo argumento, y la unidad bloqueada todavía no se valida.

**Step 4: Write minimal implementation**

Reemplaza el contenido completo de `CompleteLessonHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Modules\Academic\Application\Commands\CompleteLessonCommand;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Exceptions\LessonNotFound;
use Modules\Academic\Application\Exceptions\UnitLocked;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Application\Services\EnrollmentProgressCalculator;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Exceptions\InvalidEnrollment;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Services\CourseCurriculumUnlockCalculator;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\LessonId;

final readonly class CompleteLessonHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private EnrollmentProgressRepository $progressRepository,
        private CourseRepository $courses,
        private CourseLessonCatalog $lessonCatalog,
        private CourseCurriculumUnlockCalculator $unlockCalculator,
        private EnrollmentProgressCalculator $calculator,
    ) {}

    public function handle(CompleteLessonCommand $command): EnrollmentProgressResponse
    {
        $enrollment = $this->enrollments->findById(EnrollmentId::fromString($command->enrollmentId));
        if ($enrollment === null || $enrollment->userId() !== $command->userId) {
            throw EnrollmentNotFound::withId($command->enrollmentId);
        }

        if ($enrollment->status() !== EnrollmentStatus::Active) {
            throw InvalidEnrollment::create();
        }

        $course = $this->courses->findById($enrollment->courseId());
        assert($course instanceof Course);

        $lessonId = LessonId::fromString($command->lessonId);
        if (! in_array($lessonId->value(), $this->lessonCatalog->lessonIdsFor($course), true)) {
            throw LessonNotFound::withId($command->lessonId);
        }

        $progress = $this->progressRepository->findByEnrollmentId($enrollment->id());

        $unlockStatus = $this->unlockCalculator->statusFor($course, $progress);
        $unitId = $unlockStatus->unitIdForLesson($lessonId);
        if ($unitId !== null && ! $unlockStatus->isUnitUnlocked($unitId)) {
            throw UnitLocked::withId($unitId->value());
        }

        $progress->completeLesson($lessonId, new DateTimeImmutable('now'), $command->timeSpentMinutes);
        $this->progressRepository->save($progress);

        return $this->calculator->calculate($enrollment, $progress);
    }
}
```

**Step 5: Run test to verify it passes.** Expected: PASS (5 tests: los 4 ya existentes de ENG-036 + el nuevo).

**Step 6: Commit**

```bash
git add modules/Academic/Application/UseCases/CompleteLessonHandler.php modules/Academic/Tests/Unit/Application/CompleteLessonHandlerTest.php
git commit -m "feat(academic): block completing lessons in locked units"
```

---

### Task 4: `GetEnrollmentCurriculumStatusQuery` y su handler

**Files:**
- Create: `modules/Academic/Application/Queries/GetEnrollmentCurriculumStatusQuery.php`
- Create: `modules/Academic/Application/Responses/CurriculumUnlockResponse.php`
- Create: `modules/Academic/Application/UseCases/GetEnrollmentCurriculumStatusHandler.php`
- Test: `modules/Academic/Tests/Unit/Application/GetEnrollmentCurriculumStatusHandlerTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Queries\GetEnrollmentCurriculumStatusQuery;
use Modules\Academic\Application\Responses\CurriculumUnlockResponse;
use Modules\Academic\Application\UseCases\GetEnrollmentCurriculumStatusHandler;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\CourseCurriculumUnlockCalculator;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function getEnrollmentCurriculumStatusHandler(): GetEnrollmentCurriculumStatusHandler
{
    return new GetEnrollmentCurriculumStatusHandler(
        app(EnrollmentRepository::class),
        app(EnrollmentProgressRepository::class),
        app(CourseRepository::class),
        new CourseCurriculumUnlockCalculator(app(UnitContentRepository::class)),
    );
}

function persistedTaskCurriculumUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    app(UserRepository::class)->save($user);

    return $user->id();
}

function activeEnrollmentForCurriculumStatus(): Enrollment
{
    $course = createDraftCourseForPublishing('PRG-CURR-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: persistedTaskCurriculumUserId(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

it('devuelve el estado de curriculo al dueno del enrollment', function (): void {
    $enrollment = activeEnrollmentForCurriculumStatus();

    $response = getEnrollmentCurriculumStatusHandler()->handle(new GetEnrollmentCurriculumStatusQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: $enrollment->userId(),
        canViewOthers: false,
    ));

    expect($response)->toBeInstanceOf(CurriculumUnlockResponse::class)
        ->and($response->enrollmentId)->toBe($enrollment->id()->value())
        ->and($response->modules)->toHaveCount(1)
        ->and($response->modules[0]['units'][0]['unlocked'])->toBeTrue();
});

it('rechaza consultar el curriculo de un enrollment ajeno sin permiso ampliado', function (): void {
    $enrollment = activeEnrollmentForCurriculumStatus();

    expect(fn () => getEnrollmentCurriculumStatusHandler()->handle(new GetEnrollmentCurriculumStatusQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: persistedTaskCurriculumUserId(),
        canViewOthers: false,
    )))->toThrow(EnrollmentNotFound::class);
});

it('permite consultar el curriculo de un enrollment ajeno con permiso ampliado', function (): void {
    $enrollment = activeEnrollmentForCurriculumStatus();

    $response = getEnrollmentCurriculumStatusHandler()->handle(new GetEnrollmentCurriculumStatusQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: persistedTaskCurriculumUserId(),
        canViewOthers: true,
    ));

    expect($response->enrollmentId)->toBe($enrollment->id()->value());
});

it('rechaza consultar el curriculo de un enrollment inexistente', function (): void {
    expect(fn () => getEnrollmentCurriculumStatusHandler()->handle(new GetEnrollmentCurriculumStatusQuery(
        enrollmentId: (string) Str::uuid(),
        userId: (string) Str::uuid(),
        canViewOthers: true,
    )))->toThrow(EnrollmentNotFound::class);
});
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Application/GetEnrollmentCurriculumStatusHandlerTest.php`

Expected: FAIL — las 3 clases no existen.

**Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetEnrollmentCurriculumStatusQuery implements Query
{
    public function __construct(
        public string $enrollmentId,
        public string $userId,
        public bool $canViewOthers,
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class CurriculumUnlockResponse
{
    /**
     * @param  list<array{module_id: string, completed: bool, unlocked: bool, units: list<array{unit_id: string, completed: bool, unlocked: bool}>}>  $modules
     */
    public function __construct(
        public string $enrollmentId,
        public string $courseId,
        public array $modules,
    ) {}

    /**
     * @return array{enrollment_id: string, course_id: string, modules: list<array{module_id: string, completed: bool, unlocked: bool, units: list<array{unit_id: string, completed: bool, unlocked: bool}>}>}
     */
    public function toArray(): array
    {
        return [
            'enrollment_id' => $this->enrollmentId,
            'course_id' => $this->courseId,
            'modules' => $this->modules,
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Queries\GetEnrollmentCurriculumStatusQuery;
use Modules\Academic\Application\Responses\CurriculumUnlockResponse;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Services\CourseCurriculumUnlockCalculator;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\ModuleUnlockStatus;
use Modules\Academic\Domain\ValueObjects\UnitUnlockStatus;

final readonly class GetEnrollmentCurriculumStatusHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private EnrollmentProgressRepository $progressRepository,
        private CourseRepository $courses,
        private CourseCurriculumUnlockCalculator $unlockCalculator,
    ) {}

    public function handle(GetEnrollmentCurriculumStatusQuery $query): CurriculumUnlockResponse
    {
        $enrollment = $this->enrollments->findById(EnrollmentId::fromString($query->enrollmentId));
        if ($enrollment === null) {
            throw EnrollmentNotFound::withId($query->enrollmentId);
        }

        if ($enrollment->userId() !== $query->userId && ! $query->canViewOthers) {
            throw EnrollmentNotFound::withId($query->enrollmentId);
        }

        $course = $this->courses->findById($enrollment->courseId());
        assert($course instanceof Course);

        $progress = $this->progressRepository->findByEnrollmentId($enrollment->id());
        $status = $this->unlockCalculator->statusFor($course, $progress);

        return new CurriculumUnlockResponse(
            enrollmentId: $enrollment->id()->value(),
            courseId: $enrollment->courseId()->value(),
            modules: array_map(
                static fn (ModuleUnlockStatus $module): array => [
                    'module_id' => $module->moduleId->value(),
                    'completed' => $module->completed,
                    'unlocked' => $module->unlocked,
                    'units' => array_map(
                        static fn (UnitUnlockStatus $unit): array => [
                            'unit_id' => $unit->unitId->value(),
                            'completed' => $unit->completed,
                            'unlocked' => $unit->unlocked,
                        ],
                        $module->units,
                    ),
                ],
                $status->modules,
            ),
        );
    }
}
```

**Step 4: Run test to verify it passes.** Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Application/Queries/GetEnrollmentCurriculumStatusQuery.php modules/Academic/Application/Responses/CurriculumUnlockResponse.php modules/Academic/Application/UseCases/GetEnrollmentCurriculumStatusHandler.php modules/Academic/Tests/Unit/Application/GetEnrollmentCurriculumStatusHandlerTest.php
git commit -m "feat(academic): add get enrollment curriculum status handler"
```

---

### Task 5: Registrar la query en el provider

**Files:**
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`
- Modify: `modules/Academic/Tests/Integration/AcademicServiceProviderEnrollmentProgressTest.php`

**Step 1: Write the failing assertion**

Agrega al archivo existente (ya tiene 2 tests: binding del repositorio y registro CQRS de `CompleteLessonCommand`/`GetEnrollmentProgressQuery` — no los toques):

```php
use Modules\Academic\Application\Queries\GetEnrollmentCurriculumStatusQuery;
use Modules\Academic\Application\UseCases\GetEnrollmentCurriculumStatusHandler;

it('registra el handler de estado de curriculo en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(GetEnrollmentCurriculumStatusQuery::class))->toBe(GetEnrollmentCurriculumStatusHandler::class);
});
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Integration/AcademicServiceProviderEnrollmentProgressTest.php`

Expected: los 2 tests existentes pasan, el nuevo FALLA porque el handler no está registrado.

**Step 3: Write minimal implementation**

En `AcademicServiceProvider.php`, junto al registro de `GetEnrollmentProgressQuery` (Task 9 de ENG-036), agrega:

```php
$registry->register(
    GetEnrollmentCurriculumStatusQuery::class,
    GetEnrollmentCurriculumStatusHandler::class,
);
```

Agrega los 2 `use` correspondientes en sus bloques alfabéticos ya existentes (Queries y UseCases). No toques ninguna otra línea del archivo.

**Step 4: Run test to verify it passes.** Expected: PASS (3 tests en el archivo).

**Step 5: Commit**

```bash
git add modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php modules/Academic/Tests/Integration/AcademicServiceProviderEnrollmentProgressTest.php
git commit -m "feat(academic): register curriculum status handler"
```

---

### Task 6: API HTTP de estado de currículo

**Files:**
- Modify: `modules/Academic/Presentation/Http/Controllers/EnrollmentProgressController.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Modify: `modules/Academic/Tests/Feature/EnrollmentProgressTest.php`

**Step 1: Write the failing feature tests**

Agrega al archivo existente (17 tests ya presentes, no los toques):

```php
it('consulta el estado de curriculo propio', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $enrollment = activeEnrollmentForProgressFeature($userId);

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/curriculum")
        ->assertOk()
        ->assertJsonPath('data.enrollment_id', $enrollment->id()->value())
        ->assertJsonPath('data.modules.0.unlocked', true);
});

it('rechaza consultar el curriculo ajeno sin permiso ampliado', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $enrollment = activeEnrollmentForProgressFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/curriculum")
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});

it('permite consultar el curriculo ajeno con enrollments.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $enrollment = activeEnrollmentForProgressFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/curriculum")
        ->assertOk()
        ->assertJsonPath('data.enrollment_id', $enrollment->id()->value());
});

it('responde 404 al consultar el curriculo de un enrollment inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/academic/enrollments/'.Str::uuid().'/curriculum')
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Feature/EnrollmentProgressTest.php`

Expected: los 17 tests existentes pasan, los 4 nuevos FALLAN (ruta inexistente).

**Step 3: Write minimal implementation**

Agrega un método `curriculum` a `EnrollmentProgressController` (junto a `complete`/`show` ya existentes):

```php
public function curriculum(
    string $enrollmentId,
    Request $request,
    QueryBus $queryBus,
    PermissionChecker $permissionChecker,
): JsonResponse {
    $user = self::authenticatedUser($request);
    $result = $queryBus->ask(new GetEnrollmentCurriculumStatusQuery(
        enrollmentId: $enrollmentId,
        userId: (string) $user->getAuthIdentifier(),
        canViewOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ViewEnrollments),
    ));
    assert($result instanceof CurriculumUnlockResponse);

    return response()->json(['data' => $result->toArray()]);
}
```

Agrega los `use` correspondientes al inicio del controlador:

```php
use Modules\Academic\Application\Queries\GetEnrollmentCurriculumStatusQuery;
use Modules\Academic\Application\Responses\CurriculumUnlockResponse;
```

Ruta — en `modules/Academic/Presentation/Routes/api.php`, junto a la ruta `enrollments.progress.show` ya existente:

```php
Route::get('/enrollments/{enrollmentId}/curriculum', [EnrollmentProgressController::class, 'curriculum'])
    ->whereUuid('enrollmentId')
    ->name('enrollments.curriculum.show');
```

**Step 4: Run test to verify it passes.** Expected: PASS (21 tests).

**Step 5: Commit**

```bash
git add modules/Academic/Presentation/Http/Controllers/EnrollmentProgressController.php modules/Academic/Presentation/Routes/api.php modules/Academic/Tests/Feature/EnrollmentProgressTest.php
git commit -m "feat(academic): add enrollment curriculum status http api"
```

---

### Task 7: Verificación completa

**Files:**
- Verify: todos los archivos creados/modificados en las Tasks 1-6.

**Step 1: Ejecutar la suite completa de ENG-037**

Run:
```bash
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/Services/CourseCurriculumUnlockCalculatorTest.php modules/Academic/Tests/Unit/Application/CompleteLessonHandlerTest.php modules/Academic/Tests/Unit/Application/GetEnrollmentCurriculumStatusHandlerTest.php modules/Academic/Tests/Integration/AcademicServiceProviderEnrollmentProgressTest.php modules/Academic/Tests/Feature/EnrollmentProgressTest.php
```

Expected: PASS en todos.

**Step 2: Pint**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php vendor/bin/pint --test modules/Academic`

Expected: sin issues nuevos atribuibles a estos archivos (si Pint modifica algo, re-ejecutar Step 1).

**Step 3: PHPStan**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php vendor/bin/phpstan analyse --no-progress --memory-limit=1G modules/Academic/Domain/ValueObjects/UnitUnlockStatus.php modules/Academic/Domain/ValueObjects/ModuleUnlockStatus.php modules/Academic/Domain/ValueObjects/CurriculumUnlockStatus.php modules/Academic/Domain/Services/CourseCurriculumUnlockCalculator.php modules/Academic/Application/Exceptions/UnitLocked.php modules/Academic/Application/UseCases/CompleteLessonHandler.php modules/Academic/Application/Queries/GetEnrollmentCurriculumStatusQuery.php modules/Academic/Application/Responses/CurriculumUnlockResponse.php modules/Academic/Application/UseCases/GetEnrollmentCurriculumStatusHandler.php modules/Academic/Presentation/Http/Controllers/EnrollmentProgressController.php modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`

Expected: sin errores.

**Step 4: Verificación de rutas**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan route:list --path=academic/enrollments`

Expected: se ve la nueva ruta `enrollments/{enrollmentId}/curriculum` junto a las 10 de ENG-035/036.

**Step 5: Revisar el diff**

Run: `git log --oneline` (confirmar solo commits de ENG-037 desde el design doc) y `git status --short` (confirmar que no quedó nada de ENG-037 sin commitear ni nada ajeno tocado).

**Step 6: Actualizar roadmap y ENG-LOG**

Actualiza `docs/roadmap/ENG-000-roadmap-tecnico-backend.md` (sección ENG-037, estado `Completado` + nota) y agrega la entrada `IMP-037` en `docs/engineering/ENG-LOG.md`, siguiendo el formato de `IMP-036`. Commit aparte:

```bash
git add docs/roadmap/ENG-000-roadmap-tecnico-backend.md docs/engineering/ENG-LOG.md
git commit -m "docs(engineering): close ENG-037 progression rules"
```
