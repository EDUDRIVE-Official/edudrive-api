# ENG-036 - Seguimiento de progreso Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Registrar lecciones completadas por un estudiante en su inscripción activa y exponer un resumen de progreso (lecciones completadas, tiempo invertido, evaluaciones realizadas, porcentaje de avance, última actividad).

**Architecture:** Nuevo agregado `EnrollmentProgress` (1:1 con `Enrollment`) respaldado por una tabla de completitud por lección, con porcentaje/tiempo/última actividad calculados en la capa de aplicación (`EnrollmentProgressCalculator`) combinando `EnrollmentProgress`, el catálogo de lecciones del curso (`CourseLessonCatalog`) y los intentos de examen ya existentes (`ExamAttempt`/`Exam`). CQRS completo (`CompleteLessonCommand`/`GetEnrollmentProgressQuery`) sobre `CommandBus`/`QueryBus`, expuesto vía 2 endpoints HTTP bajo `auth:sanctum`.

**Tech Stack:** Laravel 12, Pest, Sanctum, `CommandBus`/`QueryBus` propios, PHP 8.2+, `modules/Academic` y `modules/Authorization`.

**Diseño de referencia:** `docs/plans/2026-08-15-seguimiento-progreso-eng036-design.md`

---

### Task 1: Entidad `LessonCompletion` y excepción `InvalidLessonCompletion`

**Files:**
- Create: `modules/Academic/Domain/Exceptions/InvalidLessonCompletion.php`
- Create: `modules/Academic/Domain/Entities/LessonCompletion.php`
- Test: `modules/Academic/Tests/Unit/Domain/Entities/LessonCompletionTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Entities\LessonCompletion;
use Modules\Academic\Domain\Exceptions\InvalidLessonCompletion;
use Modules\Academic\Domain\ValueObjects\LessonId;

it('crea una completitud de leccion con tiempo invertido', function (): void {
    $lessonId = LessonId::fromString((string) Str::uuid());
    $completedAt = new DateTimeImmutable('2026-08-15T10:00:00+00:00');

    $completion = LessonCompletion::create($lessonId, $completedAt, 12);

    expect($completion->lessonId()->equals($lessonId))->toBeTrue()
        ->and($completion->completedAt())->toEqual($completedAt)
        ->and($completion->timeSpentMinutes())->toBe(12);
});

it('permite tiempo invertido nulo', function (): void {
    $completion = LessonCompletion::create(LessonId::fromString((string) Str::uuid()), new DateTimeImmutable('now'), null);

    expect($completion->timeSpentMinutes())->toBeNull();
});

it('rechaza tiempo invertido negativo', function (): void {
    expect(fn () => LessonCompletion::create(LessonId::fromString((string) Str::uuid()), new DateTimeImmutable('now'), -1))
        ->toThrow(InvalidLessonCompletion::class);
});

it('actualiza completedAt y tiempo invertido preservando el lessonId', function (): void {
    $lessonId = LessonId::fromString((string) Str::uuid());
    $completion = LessonCompletion::create($lessonId, new DateTimeImmutable('2026-08-15T09:00:00+00:00'), 5);

    $updated = $completion->withCompletedAt(new DateTimeImmutable('2026-08-15T10:00:00+00:00'), 20);

    expect($updated->lessonId()->equals($lessonId))->toBeTrue()
        ->and($updated->timeSpentMinutes())->toBe(20);
});
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/Entities/LessonCompletionTest.php`

Expected: FAIL porque `LessonCompletion`/`InvalidLessonCompletion` no existen.

**Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidLessonCompletion extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El tiempo invertido en la leccion no puede ser negativo.',
            errorCode: 'INVALID_LESSON_COMPLETION',
            statusCode: 422,
        );
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use DateTimeImmutable;
use Modules\Academic\Domain\Exceptions\InvalidLessonCompletion;
use Modules\Academic\Domain\ValueObjects\LessonId;

final readonly class LessonCompletion
{
    private function __construct(
        private LessonId $lessonId,
        private DateTimeImmutable $completedAt,
        private ?int $timeSpentMinutes,
    ) {}

    public static function create(LessonId $lessonId, DateTimeImmutable $completedAt, ?int $timeSpentMinutes): self
    {
        if ($timeSpentMinutes !== null && $timeSpentMinutes < 0) {
            throw InvalidLessonCompletion::create();
        }

        return new self($lessonId, $completedAt, $timeSpentMinutes);
    }

    public function lessonId(): LessonId
    {
        return $this->lessonId;
    }

    public function completedAt(): DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function timeSpentMinutes(): ?int
    {
        return $this->timeSpentMinutes;
    }

    public function withCompletedAt(DateTimeImmutable $completedAt, ?int $timeSpentMinutes): self
    {
        return self::create($this->lessonId, $completedAt, $timeSpentMinutes);
    }
}
```

**Step 4: Run test to verify it passes**

Run: same command as Step 2.
Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Domain/Exceptions/InvalidLessonCompletion.php modules/Academic/Domain/Entities/LessonCompletion.php modules/Academic/Tests/Unit/Domain/Entities/LessonCompletionTest.php
git commit -m "feat(academic): add lesson completion entity"
```

---

### Task 2: Agregado `EnrollmentProgress`

**Files:**
- Create: `modules/Academic/Domain/Aggregates/EnrollmentProgress.php`
- Test: `modules/Academic/Tests/Unit/Domain/Aggregates/EnrollmentProgressTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\Entities\LessonCompletion;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\LessonId;

it('inicia sin lecciones completadas', function (): void {
    $progress = EnrollmentProgress::create(EnrollmentId::fromString((string) Str::uuid()));

    expect($progress->lessonCompletions())->toBe([])
        ->and($progress->completedLessonIds())->toBe([])
        ->and($progress->totalTimeSpentMinutes())->toBe(0)
        ->and($progress->lastCompletedAt())->toBeNull();
});

it('agrega una leccion completada', function (): void {
    $progress = EnrollmentProgress::create(EnrollmentId::fromString((string) Str::uuid()));
    $lessonId = LessonId::fromString((string) Str::uuid());

    $progress->completeLesson($lessonId, new DateTimeImmutable('2026-08-15T10:00:00+00:00'), 15);

    expect($progress->completedLessonIds())->toBe([$lessonId->value()])
        ->and($progress->totalTimeSpentMinutes())->toBe(15)
        ->and($progress->lastCompletedAt())->toEqual(new DateTimeImmutable('2026-08-15T10:00:00+00:00'));
});

it('completar la misma leccion dos veces actualiza en vez de duplicar', function (): void {
    $progress = EnrollmentProgress::create(EnrollmentId::fromString((string) Str::uuid()));
    $lessonId = LessonId::fromString((string) Str::uuid());

    $progress->completeLesson($lessonId, new DateTimeImmutable('2026-08-15T09:00:00+00:00'), 5);
    $progress->completeLesson($lessonId, new DateTimeImmutable('2026-08-15T11:00:00+00:00'), 20);

    expect($progress->lessonCompletions())->toHaveCount(1)
        ->and($progress->totalTimeSpentMinutes())->toBe(20)
        ->and($progress->lastCompletedAt())->toEqual(new DateTimeImmutable('2026-08-15T11:00:00+00:00'));
});

it('suma el tiempo invertido de varias lecciones e ignora los nulos', function (): void {
    $progress = EnrollmentProgress::create(EnrollmentId::fromString((string) Str::uuid()));

    $progress->completeLesson(LessonId::fromString((string) Str::uuid()), new DateTimeImmutable('2026-08-15T09:00:00+00:00'), 10);
    $progress->completeLesson(LessonId::fromString((string) Str::uuid()), new DateTimeImmutable('2026-08-15T10:00:00+00:00'), null);

    expect($progress->totalTimeSpentMinutes())->toBe(10);
});

it('restaura desde persistencia con las completitudes dadas', function (): void {
    $enrollmentId = EnrollmentId::fromString((string) Str::uuid());
    $completion = LessonCompletion::create(
        LessonId::fromString((string) Str::uuid()),
        new DateTimeImmutable('2026-08-15T10:00:00+00:00'),
        8,
    );

    $progress = EnrollmentProgress::restore($enrollmentId, [$completion]);

    expect($progress->enrollmentId()->equals($enrollmentId))->toBeTrue()
        ->and($progress->lessonCompletions())->toHaveCount(1);
});
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/EnrollmentProgressTest.php`

Expected: FAIL porque `EnrollmentProgress` no existe.

**Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Academic\Domain\Entities\LessonCompletion;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\LessonId;

final class EnrollmentProgress
{
    /** @var list<LessonCompletion> */
    private array $lessonCompletions;

    /** @param list<LessonCompletion> $lessonCompletions */
    private function __construct(
        private readonly EnrollmentId $enrollmentId,
        array $lessonCompletions,
    ) {
        $this->lessonCompletions = $lessonCompletions;
    }

    public static function create(EnrollmentId $enrollmentId): self
    {
        return new self($enrollmentId, []);
    }

    /** @param list<LessonCompletion> $lessonCompletions */
    public static function restore(EnrollmentId $enrollmentId, array $lessonCompletions): self
    {
        return new self($enrollmentId, $lessonCompletions);
    }

    public function enrollmentId(): EnrollmentId
    {
        return $this->enrollmentId;
    }

    /** @return list<LessonCompletion> */
    public function lessonCompletions(): array
    {
        return $this->lessonCompletions;
    }

    public function completeLesson(LessonId $lessonId, DateTimeImmutable $completedAt, ?int $timeSpentMinutes): void
    {
        foreach ($this->lessonCompletions as $index => $completion) {
            if ($completion->lessonId()->equals($lessonId)) {
                $this->lessonCompletions[$index] = $completion->withCompletedAt($completedAt, $timeSpentMinutes);

                return;
            }
        }

        $this->lessonCompletions[] = LessonCompletion::create($lessonId, $completedAt, $timeSpentMinutes);
    }

    /** @return list<string> */
    public function completedLessonIds(): array
    {
        return array_map(
            static fn (LessonCompletion $completion): string => $completion->lessonId()->value(),
            $this->lessonCompletions,
        );
    }

    public function totalTimeSpentMinutes(): int
    {
        return array_sum(array_map(
            static fn (LessonCompletion $completion): int => $completion->timeSpentMinutes() ?? 0,
            $this->lessonCompletions,
        ));
    }

    public function lastCompletedAt(): ?DateTimeImmutable
    {
        if ($this->lessonCompletions === []) {
            return null;
        }

        $latest = $this->lessonCompletions[0]->completedAt();
        foreach ($this->lessonCompletions as $completion) {
            if ($completion->completedAt() > $latest) {
                $latest = $completion->completedAt();
            }
        }

        return $latest;
    }
}
```

**Step 4: Run test to verify it passes**

Run: same command as Step 2.
Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Domain/Aggregates/EnrollmentProgress.php modules/Academic/Tests/Unit/Domain/Aggregates/EnrollmentProgressTest.php
git commit -m "feat(academic): add enrollment progress aggregate"
```

---

### Task 3: Servicio de dominio `CourseLessonCatalog`

**Files:**
- Create: `modules/Academic/Domain/Services/CourseLessonCatalog.php`
- Test: `modules/Academic/Tests/Unit/Domain/Services/CourseLessonCatalogTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;

uses(RefreshDatabase::class);

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
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/Services/CourseLessonCatalogTest.php`

Expected: FAIL porque `CourseLessonCatalog` no existe.

**Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Services;

use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\UnitContentRepository;

final readonly class CourseLessonCatalog
{
    public function __construct(private UnitContentRepository $unitContents) {}

    /** @return list<string> */
    public function lessonIdsFor(Course $course): array
    {
        $lessonIds = [];

        foreach ($course->modules() as $module) {
            foreach ($module->units() as $unit) {
                $content = $this->unitContents->findForCourseUnit($course->id(), $unit->id());
                if ($content === null) {
                    continue;
                }

                foreach ($content->lessons() as $lesson) {
                    $lessonIds[] = $lesson->id()->value();
                }
            }
        }

        return $lessonIds;
    }
}
```

**Step 4: Run test to verify it passes**

Run: same command as Step 2.
Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Domain/Services/CourseLessonCatalog.php modules/Academic/Tests/Unit/Domain/Services/CourseLessonCatalogTest.php
git commit -m "feat(academic): add course lesson catalog service"
```

---

### Task 4: Persistencia de `EnrollmentProgress`

**Files:**
- Create: `modules/Academic/Domain/Repositories/EnrollmentProgressRepository.php`
- Create: `modules/Academic/Infrastructure/Persistence/Migrations/2026_08_15_000001_create_academic_enrollment_lesson_completions_table.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/EnrollmentLessonCompletionModel.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentEnrollmentProgressRepository.php`
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`
- Test: `modules/Academic/Tests/Integration/EloquentEnrollmentProgressRepositoryTest.php`
- Test: `modules/Academic/Tests/Integration/AcademicServiceProviderEnrollmentProgressTest.php`

**Step 1: Write the contract**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Repositories;

use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;

interface EnrollmentProgressRepository
{
    public function save(EnrollmentProgress $progress): void;

    public function findByEnrollmentId(EnrollmentId $enrollmentId): EnrollmentProgress;
}
```

**Step 2: Write the migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_enrollment_lesson_completions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('enrollment_id')->index();
            $table->uuid('lesson_id')->index();
            $table->timestamp('completed_at');
            $table->integer('time_spent_minutes')->nullable();
            $table->timestamps();

            $table->foreign('enrollment_id')
                ->references('id')
                ->on('academic_enrollments')
                ->cascadeOnDelete();

            $table->unique(['enrollment_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_enrollment_lesson_completions');
    }
};
```

**Step 3: Write the Eloquent model**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class EnrollmentLessonCompletionModel extends Model
{
    protected $table = 'academic_enrollment_lesson_completions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'completed_at' => 'immutable_datetime',
        ];
    }
}
```

**Step 4: Write the repository implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\Entities\LessonCompletion;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\LessonId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\EnrollmentLessonCompletionModel;

final readonly class EloquentEnrollmentProgressRepository implements EnrollmentProgressRepository
{
    public function save(EnrollmentProgress $progress): void
    {
        DB::transaction(function () use ($progress): void {
            foreach ($progress->lessonCompletions() as $completion) {
                $model = EnrollmentLessonCompletionModel::query()
                    ->where('enrollment_id', $progress->enrollmentId()->value())
                    ->where('lesson_id', $completion->lessonId()->value())
                    ->first();

                if ($model !== null) {
                    $model->update([
                        'completed_at' => $completion->completedAt(),
                        'time_spent_minutes' => $completion->timeSpentMinutes(),
                    ]);

                    continue;
                }

                EnrollmentLessonCompletionModel::query()->create([
                    'id' => (string) Str::uuid(),
                    'enrollment_id' => $progress->enrollmentId()->value(),
                    'lesson_id' => $completion->lessonId()->value(),
                    'completed_at' => $completion->completedAt(),
                    'time_spent_minutes' => $completion->timeSpentMinutes(),
                ]);
            }
        });
    }

    public function findByEnrollmentId(EnrollmentId $enrollmentId): EnrollmentProgress
    {
        $completions = EnrollmentLessonCompletionModel::query()
            ->where('enrollment_id', $enrollmentId->value())
            ->orderBy('completed_at')
            ->get()
            ->map(fn (EnrollmentLessonCompletionModel $model): LessonCompletion => LessonCompletion::create(
                LessonId::fromString((string) $model->getAttribute('lesson_id')),
                $model->getAttribute('completed_at'),
                $model->getAttribute('time_spent_minutes') === null ? null : (int) $model->getAttribute('time_spent_minutes'),
            ))
            ->all();

        return EnrollmentProgress::restore($enrollmentId, array_values($completions));
    }
}
```

**Step 5: Bind the repository in the provider**

En `AcademicServiceProvider.php`, junto al bind de `EnrollmentRepository` (busca `EnrollmentRepository::class,` en el método `register()`), agrega:

```php
$this->app->bind(
    EnrollmentProgressRepository::class,
    EloquentEnrollmentProgressRepository::class,
);
```

Agrega los `use` correspondientes:

```php
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentEnrollmentProgressRepository;
```

**Step 6: Write the integration tests**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\LessonId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\EnrollmentLessonCompletionModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\EnrollmentModel;

uses(RefreshDatabase::class);

function persistedEnrollmentForProgress(): Enrollment
{
    $course = createDraftCourseForPublishing('PRG-REPO-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: (string) Str::uuid(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

it('guarda y recupera lecciones completadas', function (): void {
    $enrollment = persistedEnrollmentForProgress();
    $lessonId = LessonId::fromString((string) Str::uuid());

    $progress = EnrollmentProgress::create($enrollment->id());
    $progress->completeLesson($lessonId, new DateTimeImmutable('2026-08-15T10:00:00+00:00'), 12);

    $repository = app(EnrollmentProgressRepository::class);
    $repository->save($progress);

    $restored = $repository->findByEnrollmentId($enrollment->id());

    expect($restored->completedLessonIds())->toBe([$lessonId->value()])
        ->and($restored->totalTimeSpentMinutes())->toBe(12);
});

it('actualiza la fila existente en vez de duplicar al completar de nuevo', function (): void {
    $enrollment = persistedEnrollmentForProgress();
    $lessonId = LessonId::fromString((string) Str::uuid());
    $repository = app(EnrollmentProgressRepository::class);

    $progress = EnrollmentProgress::create($enrollment->id());
    $progress->completeLesson($lessonId, new DateTimeImmutable('2026-08-15T09:00:00+00:00'), 5);
    $repository->save($progress);

    $progress = $repository->findByEnrollmentId($enrollment->id());
    $progress->completeLesson($lessonId, new DateTimeImmutable('2026-08-15T11:00:00+00:00'), 20);
    $repository->save($progress);

    $restored = $repository->findByEnrollmentId($enrollment->id());

    expect($restored->lessonCompletions())->toHaveCount(1)
        ->and($restored->totalTimeSpentMinutes())->toBe(20);
});

it('devuelve un progreso vacio para un enrollment sin completitudes', function (): void {
    $enrollment = persistedEnrollmentForProgress();

    $restored = app(EnrollmentProgressRepository::class)->findByEnrollmentId($enrollment->id());

    expect($restored->completedLessonIds())->toBe([]);
});

it('borra en cascada las completitudes al eliminar el enrollment', function (): void {
    $enrollment = persistedEnrollmentForProgress();
    $repository = app(EnrollmentProgressRepository::class);

    $progress = EnrollmentProgress::create($enrollment->id());
    $progress->completeLesson(LessonId::fromString((string) Str::uuid()), new DateTimeImmutable('now'), 5);
    $repository->save($progress);

    EnrollmentModel::query()->where('id', $enrollment->id()->value())->delete();

    expect(EnrollmentLessonCompletionModel::query()->count())->toBe(0);
});
```

```php
<?php

declare(strict_types=1);

use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentEnrollmentProgressRepository;

it('registra el repositorio de progreso de enrollment en el contenedor', function (): void {
    expect(app(EnrollmentProgressRepository::class))->toBeInstanceOf(EloquentEnrollmentProgressRepository::class);
});
```

**Step 7: Run migrations and tests**

Run:
```bash
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Integration/EloquentEnrollmentProgressRepositoryTest.php modules/Academic/Tests/Integration/AcademicServiceProviderEnrollmentProgressTest.php
```

Expected: PASS (Pest ejecuta las migraciones automáticamente vía `RefreshDatabase`).

**Step 8: Commit**

```bash
git add modules/Academic/Domain/Repositories/EnrollmentProgressRepository.php modules/Academic/Infrastructure/Persistence/Migrations/2026_08_15_000001_create_academic_enrollment_lesson_completions_table.php modules/Academic/Infrastructure/Persistence/Eloquent/Models/EnrollmentLessonCompletionModel.php modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentEnrollmentProgressRepository.php modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php modules/Academic/Tests/Integration/EloquentEnrollmentProgressRepositoryTest.php modules/Academic/Tests/Integration/AcademicServiceProviderEnrollmentProgressTest.php
git commit -m "feat(academic): add enrollment progress persistence"
```

---

### Task 5: Excepción `LessonNotFound`

**Files:**
- Create: `modules/Academic/Application/Exceptions/LessonNotFound.php`

**Step 1: Write the exception**

No requiere test propio (es un value object de error simple, cubierto indirectamente por los tests de handler/feature de las Tasks 7 y 10).

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class LessonNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: sprintf('No existe una leccion con el identificador %s en el curso de la inscripcion.', $id),
            errorCode: 'LESSON_NOT_FOUND',
            statusCode: 404,
        );
    }
}
```

**Step 2: Commit**

```bash
git add modules/Academic/Application/Exceptions/LessonNotFound.php
git commit -m "feat(academic): add lesson not found exception"
```

---

### Task 6: `EnrollmentProgressResponse` y `EnrollmentProgressCalculator`

**Files:**
- Create: `modules/Academic/Application/Responses/EnrollmentProgressResponse.php`
- Create: `modules/Academic/Application/Services/EnrollmentProgressCalculator.php`
- Test: `modules/Academic/Tests/Unit/Application/EnrollmentProgressCalculatorTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\AnswerAttemptQuestionCommand;
use Modules\Academic\Application\Commands\StartExamAttemptCommand;
use Modules\Academic\Application\Commands\SubmitExamAttemptCommand;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Application\Services\EnrollmentProgressCalculator;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\LessonId;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;
use Modules\Foundation\Application\Bus\CommandBus;

uses(RefreshDatabase::class);

function enrollmentForCalculator(): Enrollment
{
    $course = createDraftCourseForPublishing('PRG-CALC-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: (string) Str::uuid(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

function submitExamAttemptFor(Enrollment $enrollment): void
{
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());
    $question = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::SingleChoice,
        $competencyId,
        '¿Pregunta de progreso?',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'a']),
        [
            QuestionOption::create('a', QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
            QuestionOption::create('b', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
        ],
    );
    app(QuestionRepository::class)->save($question);

    $exam = Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: $enrollment->courseId(),
        title: 'Examen de progreso',
        questions: [ExamQuestion::create(1, $question->id(), 10)],
    );
    app(ExamRepository::class)->save($exam);

    $commandBus = app(CommandBus::class);
    $attempt = $commandBus->dispatch(new StartExamAttemptCommand(examId: $exam->id()->value(), userId: $enrollment->userId()));
    assert($attempt instanceof ExamAttemptResponse);

    $commandBus->dispatch(new AnswerAttemptQuestionCommand(
        attemptId: $attempt->id,
        userId: $enrollment->userId(),
        position: 1,
        response: SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'a']),
    ));

    $commandBus->dispatch(new SubmitExamAttemptCommand(attemptId: $attempt->id, userId: $enrollment->userId()));
}

function progressCalculator(): EnrollmentProgressCalculator
{
    return new EnrollmentProgressCalculator(
        app(CourseRepository::class),
        new CourseLessonCatalog(app(UnitContentRepository::class)),
        app(ExamRepository::class),
        app(ExamAttemptRepository::class),
    );
}

it('calcula 0% sin lecciones completadas', function (): void {
    $enrollment = enrollmentForCalculator();
    $progress = EnrollmentProgress::create($enrollment->id());

    $response = progressCalculator()->calculate($enrollment, $progress);

    expect($response->totalLessons)->toBe(1)
        ->and($response->completedLessonsCount)->toBe(0)
        ->and($response->progressPercentage)->toBe(0)
        ->and($response->timeSpentMinutes)->toBe(0)
        ->and($response->evaluationsCompleted)->toBe(0)
        ->and($response->lastActivityAt)->toBeNull();
});

it('calcula 100% al completar la unica leccion del curso', function (): void {
    $enrollment = enrollmentForCalculator();
    $catalog = new CourseLessonCatalog(app(UnitContentRepository::class));
    $course = app(CourseRepository::class)->findById($enrollment->courseId());
    $lessonId = $catalog->lessonIdsFor($course)[0];

    $progress = EnrollmentProgress::create($enrollment->id());
    $progress->completeLesson(LessonId::fromString($lessonId), new DateTimeImmutable('2026-08-15T10:00:00+00:00'), 9);

    $response = progressCalculator()->calculate($enrollment, $progress);

    expect($response->progressPercentage)->toBe(100)
        ->and($response->timeSpentMinutes)->toBe(9)
        ->and($response->lastActivityAt)->toBe('2026-08-15T10:00:00+00:00');
});

it('cuenta evaluaciones enviadas del curso y las usa como ultima actividad si son mas recientes', function (): void {
    $enrollment = enrollmentForCalculator();
    submitExamAttemptFor($enrollment);

    $progress = EnrollmentProgress::create($enrollment->id());
    $progress->completeLesson(LessonId::fromString((string) Str::uuid()), new DateTimeImmutable('2020-01-01T00:00:00+00:00'), 1);

    $response = progressCalculator()->calculate($enrollment, $progress);

    expect($response->evaluationsCompleted)->toBe(1)
        ->and($response->lastActivityAt)->not->toBe('2020-01-01T00:00:00+00:00');
});
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Application/EnrollmentProgressCalculatorTest.php`

Expected: FAIL porque `EnrollmentProgressResponse`/`EnrollmentProgressCalculator` no existen.

**Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class EnrollmentProgressResponse
{
    /** @param list<string> $completedLessons */
    public function __construct(
        public string $enrollmentId,
        public string $courseId,
        public string $userId,
        public array $completedLessons,
        public int $completedLessonsCount,
        public int $totalLessons,
        public int $progressPercentage,
        public int $timeSpentMinutes,
        public int $evaluationsCompleted,
        public ?string $lastActivityAt,
    ) {}

    /** @return array{enrollment_id: string, course_id: string, user_id: string, completed_lessons: list<string>, completed_lessons_count: int, total_lessons: int, progress_percentage: int, time_spent_minutes: int, evaluations_completed: int, last_activity_at: string|null} */
    public function toArray(): array
    {
        return [
            'enrollment_id' => $this->enrollmentId,
            'course_id' => $this->courseId,
            'user_id' => $this->userId,
            'completed_lessons' => $this->completedLessons,
            'completed_lessons_count' => $this->completedLessonsCount,
            'total_lessons' => $this->totalLessons,
            'progress_percentage' => $this->progressPercentage,
            'time_spent_minutes' => $this->timeSpentMinutes,
            'evaluations_completed' => $this->evaluationsCompleted,
            'last_activity_at' => $this->lastActivityAt,
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Services;

use DateTimeImmutable;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Services\CourseLessonCatalog;

final readonly class EnrollmentProgressCalculator
{
    public function __construct(
        private CourseRepository $courses,
        private CourseLessonCatalog $lessonCatalog,
        private ExamRepository $exams,
        private ExamAttemptRepository $examAttempts,
    ) {}

    public function calculate(Enrollment $enrollment, EnrollmentProgress $progress): EnrollmentProgressResponse
    {
        $course = $this->courses->findById($enrollment->courseId());
        assert($course instanceof Course);

        $totalLessons = count($this->lessonCatalog->lessonIdsFor($course));
        $completedLessonIds = $progress->completedLessonIds();
        $completedCount = count($completedLessonIds);

        [$evaluationsCompleted, $lastExamSubmittedAt] = $this->evaluationsFor($enrollment);

        $lastActivityAt = self::latest($progress->lastCompletedAt(), $lastExamSubmittedAt);

        return new EnrollmentProgressResponse(
            enrollmentId: $enrollment->id()->value(),
            courseId: $enrollment->courseId()->value(),
            userId: $enrollment->userId(),
            completedLessons: $completedLessonIds,
            completedLessonsCount: $completedCount,
            totalLessons: $totalLessons,
            progressPercentage: $totalLessons === 0 ? 0 : (int) round($completedCount / $totalLessons * 100),
            timeSpentMinutes: $progress->totalTimeSpentMinutes(),
            evaluationsCompleted: $evaluationsCompleted,
            lastActivityAt: $lastActivityAt?->format(DATE_ATOM),
        );
    }

    /** @return array{0: int, 1: ?DateTimeImmutable} */
    private function evaluationsFor(Enrollment $enrollment): array
    {
        $count = 0;
        $lastSubmittedAt = null;

        foreach ($this->examAttempts->all(userId: $enrollment->userId(), status: ExamAttemptStatus::Submitted) as $attempt) {
            $exam = $this->exams->findById($attempt->examId());
            if ($exam === null || ! $exam->courseId()->equals($enrollment->courseId())) {
                continue;
            }

            $count++;
            $submittedAt = $attempt->submittedAt();
            if ($submittedAt !== null) {
                $lastSubmittedAt = self::latest($lastSubmittedAt, $submittedAt);
            }
        }

        return [$count, $lastSubmittedAt];
    }

    private static function latest(?DateTimeImmutable $a, ?DateTimeImmutable $b): ?DateTimeImmutable
    {
        if ($a === null) {
            return $b;
        }

        if ($b === null) {
            return $a;
        }

        return $a > $b ? $a : $b;
    }
}
```

**Step 4: Run test to verify it passes**

Run: same command as Step 2.
Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Application/Responses/EnrollmentProgressResponse.php modules/Academic/Application/Services/EnrollmentProgressCalculator.php modules/Academic/Tests/Unit/Application/EnrollmentProgressCalculatorTest.php
git commit -m "feat(academic): add enrollment progress calculator"
```

---

### Task 7: `CompleteLessonCommand` y `CompleteLessonHandler`

**Files:**
- Create: `modules/Academic/Application/Commands/CompleteLessonCommand.php`
- Create: `modules/Academic/Application/UseCases/CompleteLessonHandler.php`
- Test: `modules/Academic/Tests/Unit/Application/CompleteLessonHandlerTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\CompleteLessonCommand;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Exceptions\LessonNotFound;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Application\Services\EnrollmentProgressCalculator;
use Modules\Academic\Application\UseCases\CompleteLessonHandler;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Exceptions\InvalidEnrollment;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;

uses(RefreshDatabase::class);

function completeLessonHandler(): CompleteLessonHandler
{
    return new CompleteLessonHandler(
        app(EnrollmentRepository::class),
        app(EnrollmentProgressRepository::class),
        app(CourseRepository::class),
        new CourseLessonCatalog(app(UnitContentRepository::class)),
        new EnrollmentProgressCalculator(
            app(CourseRepository::class),
            new CourseLessonCatalog(app(UnitContentRepository::class)),
            app(ExamRepository::class),
            app(ExamAttemptRepository::class),
        ),
    );
}

function activeEnrollmentForLessonCompletion(): Enrollment
{
    $course = createDraftCourseForPublishing('PRG-CL-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: (string) Str::uuid(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

it('completa una leccion del curso de la inscripcion', function (): void {
    $enrollment = activeEnrollmentForLessonCompletion();
    $course = app(CourseRepository::class)->findById($enrollment->courseId());
    $lessonId = (new CourseLessonCatalog(app(UnitContentRepository::class)))->lessonIdsFor($course)[0];

    $response = completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: $enrollment->id()->value(),
        lessonId: $lessonId,
        userId: $enrollment->userId(),
        timeSpentMinutes: 7,
    ));

    expect($response)->toBeInstanceOf(EnrollmentProgressResponse::class)
        ->and($response->completedLessonsCount)->toBe(1)
        ->and($response->timeSpentMinutes)->toBe(7);
});

it('rechaza completar una leccion de un enrollment inexistente o ajeno', function (): void {
    $enrollment = activeEnrollmentForLessonCompletion();
    $course = app(CourseRepository::class)->findById($enrollment->courseId());
    $lessonId = (new CourseLessonCatalog(app(UnitContentRepository::class)))->lessonIdsFor($course)[0];

    expect(fn () => completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: (string) Str::uuid(),
        lessonId: $lessonId,
        userId: $enrollment->userId(),
        timeSpentMinutes: null,
    )))->toThrow(EnrollmentNotFound::class);

    expect(fn () => completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: $enrollment->id()->value(),
        lessonId: $lessonId,
        userId: (string) Str::uuid(),
        timeSpentMinutes: null,
    )))->toThrow(EnrollmentNotFound::class);
});

it('rechaza completar una leccion si el enrollment no esta activo', function (): void {
    $enrollment = activeEnrollmentForLessonCompletion();
    $enrollment->cancel();
    app(EnrollmentRepository::class)->save($enrollment);
    $course = app(CourseRepository::class)->findById($enrollment->courseId());
    $lessonId = (new CourseLessonCatalog(app(UnitContentRepository::class)))->lessonIdsFor($course)[0];

    expect(fn () => completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: $enrollment->id()->value(),
        lessonId: $lessonId,
        userId: $enrollment->userId(),
        timeSpentMinutes: null,
    )))->toThrow(InvalidEnrollment::class);
});

it('rechaza una leccion que no pertenece al curso de la inscripcion', function (): void {
    $enrollment = activeEnrollmentForLessonCompletion();

    expect(fn () => completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: $enrollment->id()->value(),
        lessonId: (string) Str::uuid(),
        userId: $enrollment->userId(),
        timeSpentMinutes: null,
    )))->toThrow(LessonNotFound::class);
});
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Application/CompleteLessonHandlerTest.php`

Expected: FAIL porque `CompleteLessonCommand`/`CompleteLessonHandler` no existen.

**Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CompleteLessonCommand implements Command
{
    public function __construct(
        public string $enrollmentId,
        public string $lessonId,
        public string $userId,
        public ?int $timeSpentMinutes = null,
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Modules\Academic\Application\Commands\CompleteLessonCommand;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Exceptions\LessonNotFound;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Application\Services\EnrollmentProgressCalculator;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Exceptions\InvalidEnrollment;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
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
        $progress->completeLesson($lessonId, new DateTimeImmutable('now'), $command->timeSpentMinutes);
        $this->progressRepository->save($progress);

        return $this->calculator->calculate($enrollment, $progress);
    }
}
```

**Step 4: Run test to verify it passes**

Run: same command as Step 2.
Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Application/Commands/CompleteLessonCommand.php modules/Academic/Application/UseCases/CompleteLessonHandler.php modules/Academic/Tests/Unit/Application/CompleteLessonHandlerTest.php
git commit -m "feat(academic): add complete lesson handler"
```

---

### Task 8: `GetEnrollmentProgressQuery` y `GetEnrollmentProgressHandler`

**Files:**
- Create: `modules/Academic/Application/Queries/GetEnrollmentProgressQuery.php`
- Create: `modules/Academic/Application/UseCases/GetEnrollmentProgressHandler.php`
- Test: `modules/Academic/Tests/Unit/Application/GetEnrollmentProgressHandlerTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Queries\GetEnrollmentProgressQuery;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Application\Services\EnrollmentProgressCalculator;
use Modules\Academic\Application\UseCases\GetEnrollmentProgressHandler;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;

uses(RefreshDatabase::class);

function getEnrollmentProgressHandler(): GetEnrollmentProgressHandler
{
    return new GetEnrollmentProgressHandler(
        app(EnrollmentRepository::class),
        app(EnrollmentProgressRepository::class),
        new EnrollmentProgressCalculator(
            app(CourseRepository::class),
            new CourseLessonCatalog(app(UnitContentRepository::class)),
            app(ExamRepository::class),
            app(ExamAttemptRepository::class),
        ),
    );
}

function activeEnrollmentForProgressQuery(): Enrollment
{
    $course = createDraftCourseForPublishing('PRG-Q-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: (string) Str::uuid(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

it('devuelve el progreso al dueno del enrollment', function (): void {
    $enrollment = activeEnrollmentForProgressQuery();

    $response = getEnrollmentProgressHandler()->handle(new GetEnrollmentProgressQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: $enrollment->userId(),
        canViewOthers: false,
    ));

    expect($response)->toBeInstanceOf(EnrollmentProgressResponse::class)
        ->and($response->enrollmentId)->toBe($enrollment->id()->value());
});

it('rechaza consultar el progreso de un enrollment ajeno sin permiso ampliado', function (): void {
    $enrollment = activeEnrollmentForProgressQuery();

    expect(fn () => getEnrollmentProgressHandler()->handle(new GetEnrollmentProgressQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: (string) Str::uuid(),
        canViewOthers: false,
    )))->toThrow(EnrollmentNotFound::class);
});

it('permite consultar el progreso de un enrollment ajeno con permiso ampliado', function (): void {
    $enrollment = activeEnrollmentForProgressQuery();

    $response = getEnrollmentProgressHandler()->handle(new GetEnrollmentProgressQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: (string) Str::uuid(),
        canViewOthers: true,
    ));

    expect($response->enrollmentId)->toBe($enrollment->id()->value());
});

it('rechaza consultar el progreso de un enrollment inexistente', function (): void {
    expect(fn () => getEnrollmentProgressHandler()->handle(new GetEnrollmentProgressQuery(
        enrollmentId: (string) Str::uuid(),
        userId: (string) Str::uuid(),
        canViewOthers: true,
    )))->toThrow(EnrollmentNotFound::class);
});
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Application/GetEnrollmentProgressHandlerTest.php`

Expected: FAIL porque `GetEnrollmentProgressQuery`/`GetEnrollmentProgressHandler` no existen.

**Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetEnrollmentProgressQuery implements Query
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

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Queries\GetEnrollmentProgressQuery;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Application\Services\EnrollmentProgressCalculator;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;

final readonly class GetEnrollmentProgressHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private EnrollmentProgressRepository $progressRepository,
        private EnrollmentProgressCalculator $calculator,
    ) {}

    public function handle(GetEnrollmentProgressQuery $query): EnrollmentProgressResponse
    {
        $enrollment = $this->enrollments->findById(EnrollmentId::fromString($query->enrollmentId));
        if ($enrollment === null) {
            throw EnrollmentNotFound::withId($query->enrollmentId);
        }

        if ($enrollment->userId() !== $query->userId && ! $query->canViewOthers) {
            throw EnrollmentNotFound::withId($query->enrollmentId);
        }

        $progress = $this->progressRepository->findByEnrollmentId($enrollment->id());

        return $this->calculator->calculate($enrollment, $progress);
    }
}
```

**Step 4: Run test to verify it passes**

Run: same command as Step 2.
Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Application/Queries/GetEnrollmentProgressQuery.php modules/Academic/Application/UseCases/GetEnrollmentProgressHandler.php modules/Academic/Tests/Unit/Application/GetEnrollmentProgressHandlerTest.php
git commit -m "feat(academic): add get enrollment progress handler"
```

---

### Task 9: Registrar CQRS de progreso en el provider

**Files:**
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`
- Modify: `modules/Academic/Tests/Integration/AcademicServiceProviderEnrollmentProgressTest.php`

**Step 1: Write the failing assertion**

Agrega al archivo de la Task 4:

```php
use Modules\Academic\Application\Commands\CompleteLessonCommand;
use Modules\Academic\Application\Queries\GetEnrollmentProgressQuery;
use Modules\Academic\Application\UseCases\CompleteLessonHandler;
use Modules\Academic\Application\UseCases\GetEnrollmentProgressHandler;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;

it('registra handlers CQRS de progreso de enrollment en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(CompleteLessonCommand::class))->toBe(CompleteLessonHandler::class)
        ->and($registry->handlerFor(GetEnrollmentProgressQuery::class))->toBe(GetEnrollmentProgressHandler::class);
});
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Integration/AcademicServiceProviderEnrollmentProgressTest.php`

Expected: FAIL porque los handlers no están registrados.

**Step 3: Write minimal implementation**

En `AcademicServiceProvider.php`, junto al registro de `CreateEnrollmentCommand`/`GetEnrollmentQuery` (busca el bloque de `$registry->register(` para enrollments), agrega:

```php
$registry->register(
    CompleteLessonCommand::class,
    CompleteLessonHandler::class,
);

$registry->register(
    GetEnrollmentProgressQuery::class,
    GetEnrollmentProgressHandler::class,
);
```

Agrega los `use` correspondientes junto a los de enrollment ya existentes.

**Step 4: Run test to verify it passes**

Run: same command as Step 2.
Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php modules/Academic/Tests/Integration/AcademicServiceProviderEnrollmentProgressTest.php
git commit -m "feat(academic): register enrollment progress handlers"
```

---

### Task 10: API HTTP de progreso

**Files:**
- Create: `modules/Academic/Presentation/Http/Requests/CompleteLessonRequest.php`
- Create: `modules/Academic/Presentation/Http/Controllers/EnrollmentProgressController.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Test: `modules/Academic/Tests/Feature/EnrollmentProgressTest.php`

**Step 1: Write the failing feature tests**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

function activeEnrollmentForProgressFeature(?string $userId = null): Enrollment
{
    $course = createDraftCourseForPublishing('PRG-FEAT-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: $userId ?? (string) Str::uuid(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

function firstLessonIdFor(Enrollment $enrollment): string
{
    $course = app(CourseRepository::class)->findById($enrollment->courseId());

    return (new CourseLessonCatalog(app(UnitContentRepository::class)))->lessonIdsFor($course)[0];
}

it('requires authentication to complete a lesson', function (): void {
    /** @var TestCase $this */
    $enrollment = activeEnrollmentForProgressFeature();

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/lessons/".Str::uuid().'/complete')
        ->assertUnauthorized();
});

it('completa una leccion propia', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $enrollment = activeEnrollmentForProgressFeature($userId);
    $lessonId = firstLessonIdFor($enrollment);

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/lessons/{$lessonId}/complete", [
        'time_spent_minutes' => 10,
    ])->assertOk()
        ->assertJsonPath('data.completed_lessons_count', 1)
        ->assertJsonPath('data.time_spent_minutes', 10)
        ->assertJsonPath('data.progress_percentage', 100);
});

it('rechaza completar una leccion ajena', function (): void {
    /** @var TestCase $this */
    actingAsUserId((string) Str::uuid());
    $enrollment = activeEnrollmentForProgressFeature();
    $lessonId = firstLessonIdFor($enrollment);

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/lessons/{$lessonId}/complete")
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});

it('rechaza completar una leccion inexistente en el curso', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $enrollment = activeEnrollmentForProgressFeature($userId);

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/lessons/".Str::uuid().'/complete')
        ->assertNotFound()
        ->assertJsonPath('code', 'LESSON_NOT_FOUND');
});

it('rechaza completar una leccion si el enrollment no esta activo', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $enrollment = activeEnrollmentForProgressFeature($userId);
    $enrollment->cancel();
    app(EnrollmentRepository::class)->save($enrollment);
    $lessonId = firstLessonIdFor($enrollment);

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/lessons/{$lessonId}/complete")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'INVALID_ENROLLMENT');
});

it('valida que time_spent_minutes no sea negativo', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $enrollment = activeEnrollmentForProgressFeature($userId);
    $lessonId = firstLessonIdFor($enrollment);

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/lessons/{$lessonId}/complete", [
        'time_spent_minutes' => -1,
    ])->assertUnprocessable();
});

it('consulta el progreso propio', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $enrollment = activeEnrollmentForProgressFeature($userId);

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/progress")
        ->assertOk()
        ->assertJsonPath('data.enrollment_id', $enrollment->id()->value());
});

it('rechaza consultar el progreso ajeno sin permiso ampliado', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $enrollment = activeEnrollmentForProgressFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/progress")
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});

it('permite consultar el progreso ajeno con enrollments.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $enrollment = activeEnrollmentForProgressFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/progress")
        ->assertOk()
        ->assertJsonPath('data.enrollment_id', $enrollment->id()->value());
});

it('responde 404 al consultar el progreso de un enrollment inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/academic/enrollments/'.Str::uuid().'/progress')
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});
```

> **Nota:** este archivo usa `actingAsUserId($userId)`, un helper nuevo que faltará junto con la ruta — se agrega en el Step 3 porque `actingAsRole()` siempre genera un usuario nuevo y no permite fijar el `user_id` que necesitamos para que coincida con el dueño del enrollment. Añádelo a `tests/Pest.php` junto a `actingAsRole`:
>
> ```php
> function actingAsUserId(string $userId): UserModel
> {
>     $repository = app(UserRepository::class);
>
>     $user = User::register(
>         id: $userId,
>         name: 'Usuario de prueba',
>         email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
>         passwordHash: 'hashed-password',
>     );
>
>     $repository->save($user);
>
>     $model = UserModel::query()->findOrFail($user->id());
>
>     Sanctum::actingAs($model);
>
>     return $model;
> }
> ```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Feature/EnrollmentProgressTest.php`

Expected: FAIL porque el controller/rutas/request no existen (una vez agregado `actingAsUserId` a `tests/Pest.php`).

**Step 3: Write minimal implementation**

`CompleteLessonRequest`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CompleteLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'time_spent_minutes' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
```

`EnrollmentProgressController`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Application\Commands\CompleteLessonCommand;
use Modules\Academic\Application\Queries\GetEnrollmentProgressQuery;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Presentation\Http\Requests\CompleteLessonRequest;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;

final class EnrollmentProgressController
{
    public function complete(
        string $enrollmentId,
        string $lessonId,
        CompleteLessonRequest $request,
        CommandBus $commandBus,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $result = $commandBus->dispatch(new CompleteLessonCommand(
            enrollmentId: $enrollmentId,
            lessonId: $lessonId,
            userId: (string) $user->getAuthIdentifier(),
            timeSpentMinutes: $request->validated('time_spent_minutes') === null
                ? null
                : (int) $request->validated('time_spent_minutes'),
        ));
        assert($result instanceof EnrollmentProgressResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function show(
        string $enrollmentId,
        Request $request,
        QueryBus $queryBus,
        PermissionChecker $permissionChecker,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetEnrollmentProgressQuery(
            enrollmentId: $enrollmentId,
            userId: (string) $user->getAuthIdentifier(),
            canViewOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ViewEnrollments),
        ));
        assert($result instanceof EnrollmentProgressResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    private static function authenticatedUser(Request $request): Authenticatable
    {
        $user = $request->user();
        assert($user instanceof Authenticatable);

        return $user;
    }
}
```

Rutas — dentro del grupo `auth:sanctum` de `modules/Academic/Presentation/Routes/api.php`, junto a las rutas de enrollments ya existentes:

```php
use Modules\Academic\Presentation\Http\Controllers\EnrollmentProgressController;
```

```php
Route::post('/enrollments/{enrollmentId}/lessons/{lessonId}/complete', [EnrollmentProgressController::class, 'complete'])
    ->whereUuid('enrollmentId')
    ->whereUuid('lessonId')
    ->name('enrollments.lessons.complete');

Route::get('/enrollments/{enrollmentId}/progress', [EnrollmentProgressController::class, 'show'])
    ->whereUuid('enrollmentId')
    ->name('enrollments.progress.show');
```

**Step 4: Run test to verify it passes**

Run: same command as Step 2.
Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Presentation/Http/Requests/CompleteLessonRequest.php modules/Academic/Presentation/Http/Controllers/EnrollmentProgressController.php modules/Academic/Presentation/Routes/api.php modules/Academic/Tests/Feature/EnrollmentProgressTest.php tests/Pest.php
git commit -m "feat(academic): add enrollment progress http api"
```

---

### Task 11: Verificación completa

**Files:**
- Verify: todos los archivos de ENG-036 creados/modificados en las Tasks 1-10.

**Step 1: Ejecutar la suite completa de ENG-036**

Run:
```bash
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/Entities/LessonCompletionTest.php modules/Academic/Tests/Unit/Domain/Aggregates/EnrollmentProgressTest.php modules/Academic/Tests/Unit/Domain/Services/CourseLessonCatalogTest.php modules/Academic/Tests/Integration/EloquentEnrollmentProgressRepositoryTest.php modules/Academic/Tests/Integration/AcademicServiceProviderEnrollmentProgressTest.php modules/Academic/Tests/Unit/Application/EnrollmentProgressCalculatorTest.php modules/Academic/Tests/Unit/Application/CompleteLessonHandlerTest.php modules/Academic/Tests/Unit/Application/GetEnrollmentProgressHandlerTest.php modules/Academic/Tests/Feature/EnrollmentProgressTest.php
```

Expected: PASS en todos.

**Step 2: Pint**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php vendor/bin/pint modules/Academic tests/Pest.php`

Expected: sin errores (o corregidos automáticamente); re-ejecutar la suite de Step 1 si Pint modificó algo.

**Step 3: PHPStan**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php vendor/bin/phpstan analyse --no-progress --memory-limit=1G modules/Academic`

Expected: sin errores nuevos atribuibles a ENG-036 (si aparecen errores preexistentes de ENG-032/033/034/035 sin consolidar, no son responsabilidad de este plan).

**Step 4: Verificación de rutas**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan route:list --path=academic/enrollments`

Expected: se ven las 2 rutas nuevas (`lessons/{lessonId}/complete`, `progress`) junto a las 8 de ENG-035.

**Step 5: Revisar el diff completo**

Run: `git diff --stat` (sin nada staged de otras historias) y `git log --oneline -12` para confirmar que solo aparecen los commits de ENG-036.

**Step 6: Actualizar roadmap y ENG-LOG**

Actualiza `docs/roadmap/ENG-000-roadmap-tecnico-backend.md` (sección ENG-036, estado `Completado` + nota) y agrega la entrada `IMP-036` en `docs/engineering/ENG-LOG.md`, siguiendo el formato de las entradas `IMP-032`/`IMP-034` ya existentes. Commit aparte:

```bash
git add docs/roadmap/ENG-000-roadmap-tecnico-backend.md docs/engineering/ENG-LOG.md
git commit -m "docs(engineering): close ENG-036 progress tracking"
```
