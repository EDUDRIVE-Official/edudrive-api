# ENG-031 — Exámenes y cuestionarios — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implementar la definición/configuración de exámenes (ENG-031) en el módulo Academic: agregado `Exam` anclado a un curso, con lista ordenada de preguntas del banco ENG-030 (posición + puntos), configuración (duración, intentos, regla de aprobación, barajado, modo de retroalimentación), CQRS completo (create/update/delete/get/list), API HTTP protegida con permisos `exams.manage`/`exams.view`, todo con TDD.

**Architecture:** Hexagonal dentro de `Modules\Academic` (Domain → Application → Infrastructure → Presentation), CQRS vía `CommandBus`/`QueryBus` y `MessageHandlerRegistry`. El agregado `Exam` es la plantilla reutilizable (sin estados); sus preguntas son entidades hijas `ExamQuestion` (position, questionId, points) en tabla pivot normalizada. Solo definición: intentos (ENG-032), motor de calificación (ENG-033) y examen teórico (ENG-034) quedan diferidos.

**Tech Stack:** PHP 8.4, Laravel, Eloquent, PostgreSQL + SQLite (tests), Pest, PHPStan nivel 8, Pint. Validación solo dentro del contenedor desechable con `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test <path>`.

**Design doc:** `docs/plans/2026-08-11-examenes-cuestionarios-eng031-design.md` (commiteado en `bda82bf`).

---

## Convenciones del proyecto (leer antes de empezar)

- Todos los comandos de test/Pint/PHPStan/migrate se ejecutan dentro del contenedor **desechable** (patrón arriba). El contenedor fijo `edudrive-app` monta una copia stale; NO usarlo.
- PHPStan lento: `vendor/bin/phpstan analyse --no-progress --memory-limit=1G` con timeout generoso.
- Pest aísla archivos de test: helpers compartidos van en `tests/Pest.php`, no en archivos concretos.
- Los datasets de Pest NO deben ser `static`.
- Errores públicos: extender `Modules\Foundation\Domain\Exceptions\DomainException` (message, errorCode, statusCode). Render global en `bootstrap/app.php` los convierte a `{message, status, code}` para rutas `api/*`.
- Errores de dominio de validación: `InvalidExam::create()` (422). Not-found de aplicación: `ExamNotFound::withId($id)` (404). Ver `modules/Academic/Application/Exceptions/QuestionNotFound.php` como plantilla.
- Commit style: `feat(academic): ...`, `feat(authorization): ...`, `docs(engineering): ...`. Frecuente, tras cada tarea verde.
- ValueObjects de UUID siguen `QuestionId` (regex `/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/` + `strtolower(trim())`).
- Reutilizar `CourseNotFound` (404 `COURSE_NOT_FOUND`) y `QuestionNotFound` (404 `QUESTION_NOT_FOUND`) existentes para validar referencias en handlers.
- Referencias útiles de ENG-030 (patrones a copiar): `QuestionId.php`, `InvalidQuestion.php`, `Question.php` (agregado), `QuestionRepository.php`, `EloquentQuestionRepository.php`, `QuestionController.php`, `CreateQuestionRequest.php`, `tests/Pest.php` (`persistedQuestionCompetencyId`, `actingAsRole`).

## Vista previa de archivos (mapa)

**Domain** (crear):
- `Domain/Enums/ExamFeedbackMode.php`
- `Domain/ValueObjects/ExamId.php`
- `Domain/Entities/ExamQuestion.php`
- `Domain/Exceptions/InvalidExam.php` (422 `INVALID_EXAM`)
- `Domain/Aggregates/Exam.php`
- `Domain/Repositories/ExamRepository.php`

**Application** (crear):
- `Commands/CreateExamCommand.php`
- `Commands/UpdateExamCommand.php`
- `Commands/DeleteExamCommand.php`
- `Queries/GetExamQuery.php`
- `Queries/ListExamsQuery.php`
- `UseCases/CreateExamHandler.php`
- `UseCases/UpdateExamHandler.php`
- `UseCases/DeleteExamHandler.php`
- `UseCases/GetExamHandler.php`
- `UseCases/ListExamsHandler.php`
- `Exceptions/ExamNotFound.php` (404 `EXAM_NOT_FOUND`)
- `Responses/ExamResponse.php`
- `Responses/ExamListItemResponse.php`

**Infrastructure** (crear):
- `Persistence/Migrations/2026_08_11_000001_create_academic_exams_tables.php`
- `Persistence/Eloquent/Models/ExamModel.php`
- `Persistence/Eloquent/Models/ExamQuestionModel.php`
- `Persistence/Eloquent/Repositories/EloquentExamRepository.php`

**Infrastructure** (modificar):
- `Infrastructure/Providers/AcademicServiceProvider.php` (bind + registrar handlers)

**Presentation** (crear):
- `Http/Controllers/ExamController.php`
- `Http/Requests/CreateExamRequest.php`
- `Http/Requests/UpdateExamRequest.php`

**Presentation** (modificar):
- `Presentation/Routes/api.php` (5 rutas)

**Authorization** (modificar):
- `modules/Authorization/Domain/Enums/Permission.php` (2 permisos)
- `modules/Authorization/Domain/Services/RolePermissions.php` (grants)
- `modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php` (asserts)

**Tests** (crear):
- `modules/Academic/Tests/Unit/Domain/Aggregates/ExamTest.php`
- `modules/Academic/Tests/Unit/Application/ExamHandlerTest.php`
- `modules/Academic/Tests/Integration/EloquentExamRepositoryTest.php`
- `modules/Academic/Tests/Feature/ExamTest.php`

**tests** (modificar):
- `tests/Pest.php` (helper `persistedExamCourseId`)

---

### Task 1: Migración de exámenes

**Files:**
- Create: `modules/Academic/Infrastructure/Persistence/Migrations/2026_08_11_000001_create_academic_exams_tables.php`

**Step 1: Write the migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        }

        Schema::create('academic_exams', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained('academic_courses')->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('description', 2000)->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->integer('max_attempts')->default(1);
            $table->smallInteger('passing_score')->default(60);
            $table->boolean('shuffle_questions')->default(false);
            $table->string('feedback_mode', 20)->default('none');
            $table->timestampsTz();
        });

        Schema::create('academic_exam_questions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_id')->constrained('academic_exams')->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained('academic_questions')->cascadeOnDelete();
            $table->integer('position');
            $table->integer('points')->default(1);
            $table->timestampsTz();
            $table->unique(['exam_id', 'position']);
            $table->unique(['exam_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_exam_questions');
        Schema::dropIfExists('academic_exams');
    }
};
```

**Step 2: Apply and verify**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan migrate --force`
Expected: migración ejecutada (o "Nothing to migrate" si ya corre la suite en otra DB; en dev Postgres se aplica).

**Step 3: Commit**

```bash
git add modules/Academic/Infrastructure/Persistence/Migrations/2026_08_11_000001_create_academic_exams_tables.php
git commit -m "feat(academic): add exams tables migration"
```

---

### Task 2: Value objects, enum y excepción de examen

**Files:**
- Create: `modules/Academic/Domain/ValueObjects/ExamId.php`
- Create: `modules/Academic/Domain/Enums/ExamFeedbackMode.php`
- Create: `modules/Academic/Domain/Exceptions/InvalidExam.php`

**Step 1: Write the value objects**

`ExamId` (copia `QuestionId`, cambia mensaje a "El identificador del examen debe ser un UUID válido.").

`ExamFeedbackMode`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum ExamFeedbackMode: string
{
    case None = 'none';
    case AfterSubmission = 'after_submission';
    case Immediate = 'immediate';
}
```

`InvalidExam` (copia `InvalidQuestion`, cambia errorCode a `INVALID_EXAM` y mensaje a "El examen no es válido.").

**Step 2: Verify with a smoke test**

No hay test nuevo en esta tarea (los VOs se cubren vía el agregado). Correr la suite de dominio para confirmar que no rompe nada:

Run: `... php artisan test modules/Academic/Tests/Unit/Domain` (con el patrón de contenedor)
Expected: PASS.

**Step 3: Commit**

```bash
git add modules/Academic/Domain/ValueObjects/ExamId.php modules/Academic/Domain/Enums/ExamFeedbackMode.php modules/Academic/Domain/Exceptions/InvalidExam.php
git commit -m "feat(academic): add exam id, feedback mode enum and exception"
```

---

### Task 3: Entidad ExamQuestion

**Files:**
- Create: `modules/Academic/Domain/Entities/ExamQuestion.php`

**Step 1: Write the entity**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use Modules\Academic\Domain\Exceptions\InvalidExam;
use Modules\Academic\Domain\ValueObjects\QuestionId;

final readonly class ExamQuestion
{
    private function __construct(
        private int $position,
        private QuestionId $questionId,
        private int $points,
    ) {}

    public static function create(int $position, QuestionId $questionId, int $points): self
    {
        if ($position < 1 || $points < 1) {
            throw InvalidExam::create();
        }

        return new self($position, $questionId, $points);
    }

    public function position(): int
    {
        return $this->position;
    }

    public function questionId(): QuestionId
    {
        return $this->questionId;
    }

    public function points(): int
    {
        return $this->points;
    }
}
```

**Step 2: Verify**

Run: `... php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/ExamTest.php` (aún no existe; se crea en Task 4 — aquí solo validar que no rompe la suite con `php artisan test modules/Academic/Tests/Unit/Domain`)
Expected: PASS (sin regresiones).

**Step 3: Commit**

```bash
git add modules/Academic/Domain/Entities/ExamQuestion.php
git commit -m "feat(academic): add exam question entity"
```

---

### Task 4: Agregado Exam (TDD)

**Files:**
- Create: `modules/Academic/Domain/Aggregates/Exam.php`
- Test: `modules/Academic/Tests/Unit/Domain/Aggregates/ExamTest.php`

**Step 1: Write the failing tests**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Exceptions\InvalidExam;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\QuestionId;

/** @return list<ExamQuestion> */
function examQuestions(array $ids): array
{
    return array_map(
        static fn (string $id, int $index): ExamQuestion => ExamQuestion::create($index + 1, QuestionId::fromString($id), 1),
        $ids,
        array_keys($ids),
    );
}

it('crea un examen con su configuración y preguntas', function (): void {
    $exam = Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        title: 'Examen teórico B1',
        description: 'Evaluación final del curso.',
        durationMinutes: 45,
        maxAttempts: 2,
        passingScore: 70,
        shuffleQuestions: true,
        feedbackMode: ExamFeedbackMode::AfterSubmission,
        questions: examQuestions([(string) Str::uuid(), (string) Str::uuid()]),
    );

    expect($exam->title())->toBe('Examen teórico B1')
        ->and($exam->maxAttempts())->toBe(2)
        ->and($exam->passingScore())->toBe(70)
        ->and($exam->shuffleQuestions())->toBeTrue()
        ->and($exam->feedbackMode())->toBe(ExamFeedbackMode::AfterSubmission)
        ->and($exam->questions())->toHaveCount(2);
});

it('rechaza un examen sin preguntas', function (): void {
    expect(fn () => Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        title: 'Examen vacío',
        questions: [],
    ))->toThrow(InvalidExam::class);
});

it('rechaza un examen con preguntas duplicadas', function (): void {
    $questionId = (string) Str::uuid();
    expect(fn () => Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        title: 'Examen duplicado',
        questions: examQuestions([$questionId, $questionId]),
    ))->toThrow(InvalidExam::class);
});

it('rechaza un título vacío', function (): void {
    expect(fn () => Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        title: '   ',
        questions: examQuestions([(string) Str::uuid()]),
    ))->toThrow(InvalidExam::class);
});

it('rechaza un passing score fuera de rango', function (): void {
    expect(fn () => Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        title: 'Examen',
        passingScore: 0,
        questions: examQuestions([(string) Str::uuid()]),
    ))->toThrow(InvalidExam::class);
});

it('rechaza intentos en cero', function (): void {
    expect(fn () => Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        title: 'Examen',
        maxAttempts: 0,
        questions: examQuestions([(string) Str::uuid()]),
    ))->toThrow(InvalidExam::class);
});

it('rechaza una duración en cero', function (): void {
    expect(fn () => Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        title: 'Examen',
        durationMinutes: 0,
        questions: examQuestions([(string) Str::uuid()]),
    ))->toThrow(InvalidExam::class);
});

it('reemplaza los datos del examen conservando curso e id', function (): void {
    $exam = Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        title: 'Original',
        questions: examQuestions([(string) Str::uuid()]),
    );

    $exam->replace(
        title: 'Actualizado',
        description: null,
        durationMinutes: null,
        maxAttempts: 3,
        passingScore: 80,
        shuffleQuestions: false,
        feedbackMode: ExamFeedbackMode::None,
        questions: examQuestions([(string) Str::uuid(), (string) Str::uuid()]),
    );

    expect($exam->title())->toBe('Actualizado')
        ->and($exam->maxAttempts())->toBe(3)
        ->and($exam->questions())->toHaveCount(2);
});
```

**Step 2: Run to verify fail**

Run: `... php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/ExamTest.php`
Expected: FAIL (clase `Exam` no existe).

**Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Exceptions\InvalidExam;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamId;

final class Exam
{
    private const int MAX_TITLE_LENGTH = 180;

    private const int MAX_DESCRIPTION_LENGTH = 2000;

    /** @param list<ExamQuestion> $questions */
    private function __construct(
        private ExamId $id,
        private CourseId $courseId,
        private string $title,
        private ?string $description,
        private ?int $durationMinutes,
        private int $maxAttempts,
        private int $passingScore,
        private bool $shuffleQuestions,
        private ExamFeedbackMode $feedbackMode,
        private array $questions,
    ) {}

    /** @param list<ExamQuestion> $questions */
    public static function create(
        ExamId $id,
        CourseId $courseId,
        string $title,
        array $questions,
        ?string $description = null,
        ?int $durationMinutes = null,
        int $maxAttempts = 1,
        int $passingScore = 60,
        bool $shuffleQuestions = false,
        ExamFeedbackMode $feedbackMode = ExamFeedbackMode::None,
    ): self {
        $exam = new self($id, $courseId, $title, $description, $durationMinutes, $maxAttempts, $passingScore, $shuffleQuestions, $feedbackMode, $questions);
        $exam->assertValid();

        return $exam;
    }

    /** @param list<ExamQuestion> $questions */
    public static function restore(
        ExamId $id,
        CourseId $courseId,
        string $title,
        array $questions,
        ?string $description = null,
        ?int $durationMinutes = null,
        int $maxAttempts = 1,
        int $passingScore = 60,
        bool $shuffleQuestions = false,
        ExamFeedbackMode $feedbackMode = ExamFeedbackMode::None,
    ): self {
        $exam = new self($id, $courseId, $title, $description, $durationMinutes, $maxAttempts, $passingScore, $shuffleQuestions, $feedbackMode, $questions);
        $exam->assertValid();

        return $exam;
    }

    /** @param list<ExamQuestion> $questions */
    public function replace(
        string $title,
        array $questions,
        ?string $description = null,
        ?int $durationMinutes = null,
        int $maxAttempts = 1,
        int $passingScore = 60,
        bool $shuffleQuestions = false,
        ExamFeedbackMode $feedbackMode = ExamFeedbackMode::None,
    ): void {
        $next = new self($this->id, $this->courseId, $title, $description, $durationMinutes, $maxAttempts, $passingScore, $shuffleQuestions, $feedbackMode, $questions);
        $next->assertValid();

        $this->title = $next->title;
        $this->description = $next->description;
        $this->durationMinutes = $next->durationMinutes;
        $this->maxAttempts = $next->maxAttempts;
        $this->passingScore = $next->passingScore;
        $this->shuffleQuestions = $next->shuffleQuestions;
        $this->feedbackMode = $next->feedbackMode;
        $this->questions = $next->questions;
    }

    public function id(): ExamId
    {
        return $this->id;
    }

    public function courseId(): CourseId
    {
        return $this->courseId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function durationMinutes(): ?int
    {
        return $this->durationMinutes;
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function passingScore(): int
    {
        return $this->passingScore;
    }

    public function shuffleQuestions(): bool
    {
        return $this->shuffleQuestions;
    }

    public function feedbackMode(): ExamFeedbackMode
    {
        return $this->feedbackMode;
    }

    /** @return list<ExamQuestion> */
    public function questions(): array
    {
        return $this->questions;
    }

    private function assertValid(): void
    {
        $this->title = trim($this->title);
        if ($this->title === '' || strlen($this->title) > self::MAX_TITLE_LENGTH) {
            throw InvalidExam::create();
        }

        $this->description = self::optionalString($this->description, self::MAX_DESCRIPTION_LENGTH);

        if ($this->durationMinutes !== null && $this->durationMinutes < 1) {
            throw InvalidExam::create();
        }

        if ($this->maxAttempts < 1) {
            throw InvalidExam::create();
        }

        if ($this->passingScore < 1 || $this->passingScore > 100) {
            throw InvalidExam::create();
        }

        if ($this->questions === []) {
            throw InvalidExam::create();
        }

        $questionIds = array_map(
            static fn (ExamQuestion $question): string => $question->questionId()->value(),
            $this->questions,
        );
        if (count(array_unique($questionIds)) !== count($questionIds)) {
            throw InvalidExam::create();
        }

        $positions = array_map(static fn (ExamQuestion $question): int => $question->position(), $this->questions);
        if (array_values($positions) !== range(1, count($positions))) {
            throw InvalidExam::create();
        }
    }

    private static function optionalString(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || strlen($value) > $maxLength) {
            throw InvalidExam::create();
        }

        return $value;
    }
}
```

**Step 4: Run to verify pass**

Run: `... php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/ExamTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Domain/Aggregates/Exam.php modules/Academic/Tests/Unit/Domain/Aggregates/ExamTest.php
git commit -m "feat(academic): add exam aggregate with question validation"
```

---

### Task 5: Contrato ExamRepository

**Files:**
- Create: `modules/Academic/Domain/Repositories/ExamRepository.php`

**Step 1: Write the interface**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Repositories;

use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamId;

interface ExamRepository
{
    public function save(Exam $exam): void;

    public function findById(ExamId $id): ?Exam;

    /** @return list<Exam> */
    public function all(?CourseId $courseId = null): array;

    public function delete(ExamId $id): void;
}
```

**Step 2: Commit**

```bash
git add modules/Academic/Domain/Repositories/ExamRepository.php
git commit -m "feat(academic): add exam repository contract"
```

---

### Task 6: Comandos, consultas y excepción de aplicación

**Files:**
- Create: `modules/Academic/Application/Commands/CreateExamCommand.php`
- Create: `modules/Academic/Application/Commands/UpdateExamCommand.php`
- Create: `modules/Academic/Application/Commands/DeleteExamCommand.php`
- Create: `modules/Academic/Application/Queries/GetExamQuery.php`
- Create: `modules/Academic/Application/Queries/ListExamsQuery.php`
- Create: `modules/Academic/Application/Exceptions/ExamNotFound.php`

**Step 1: Write the messages**

`CreateExamCommand` (copia `CreateQuestionCommand`, campos: courseId, title, description?, durationMinutes?, maxAttempts, passingScore, shuffleQuestions, feedbackMode, questions: `list<array{questionId: string, points: int}>`).

`UpdateExamCommand` (igual que Create pero con `examId` primero y **sin** `courseId` — no se re-ancla).

`DeleteExamCommand(examId)`.

`GetExamQuery(examId)`, `ListExamsQuery(?courseId = null)`.

`ExamNotFound` (copia `QuestionNotFound`, cambia mensaje a "No existe un examen con el identificador %s." y errorCode a `EXAM_NOT_FOUND`).

**Step 2: Commit**

```bash
git add modules/Academic/Application/Commands/CreateExamCommand.php modules/Academic/Application/Commands/UpdateExamCommand.php modules/Academic/Application/Commands/DeleteExamCommand.php modules/Academic/Application/Queries/GetExamQuery.php modules/Academic/Application/Queries/ListExamsQuery.php modules/Academic/Application/Exceptions/ExamNotFound.php
git commit -m "feat(academic): add exam commands, queries and not found exception"
```

---

### Task 7: Respuestas de aplicación y handlers (TDD)

**Files:**
- Create: `modules/Academic/Application/Responses/ExamResponse.php`
- Create: `modules/Academic/Application/Responses/ExamListItemResponse.php`
- Create: `modules/Academic/Application/UseCases/CreateExamHandler.php`
- Create: `modules/Academic/Application/UseCases/UpdateExamHandler.php`
- Create: `modules/Academic/Application/UseCases/DeleteExamHandler.php`
- Create: `modules/Academic/Application/UseCases/GetExamHandler.php`
- Create: `modules/Academic/Application/UseCases/ListExamsHandler.php`
- Test: `modules/Academic/Tests/Unit/Application/ExamHandlerTest.php`

**Step 1: Write the failing tests**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\CreateExamCommand;
use Modules\Academic\Application\Commands\DeleteExamCommand;
use Modules\Academic\Application\Commands\UpdateExamCommand;
use Modules\Academic\Application\Exceptions\ExamNotFound;
use Modules\Academic\Application\Queries\GetExamQuery;
use Modules\Academic\Application\Queries\ListExamsQuery;
use Modules\Academic\Application\Responses\ExamResponse;
use Modules\Academic\Application\UseCases\CreateExamHandler;
use Modules\Academic\Application\UseCases\DeleteExamHandler;
use Modules\Academic\Application\UseCases\GetExamHandler;
use Modules\Academic\Application\UseCases\ListExamsHandler;
use Modules\Academic\Application\UseCases\UpdateExamHandler;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\QuestionId;

final class InMemoryExamRepository implements ExamRepository
{
    /** @var array<string, Exam> */
    public array $exams = [];

    public int $saveCalls = 0;

    public function save(Exam $exam): void
    {
        $this->saveCalls++;
        $this->exams[$exam->id()->value()] = $exam;
    }

    public function findById(ExamId $id): ?Exam
    {
        return $this->exams[$id->value()] ?? null;
    }

    public function all(?CourseId $courseId = null): array
    {
        $all = array_values($this->exams);
        if ($courseId === null) {
            return $all;
        }

        return array_values(array_filter(
            $all,
            static fn (Exam $exam): bool => $exam->courseId()->equals($courseId),
        ));
    }

    public function delete(ExamId $id): void
    {
        unset($this->exams[$id->value()]);
    }
}

/** @return list<array{questionId: string, points: int}> */
function examQuestionPayloads(array $ids): array
{
    return array_map(static fn (string $id): array => ['questionId' => $id, 'points' => 1], $ids);
}

/** Persists a course and two questions for exam handler tests, returning [courseId, questionIds]. */
function persistedExamFixtures(): array
{
    $courseRepository = app(CourseRepository::class);
    $course = \Modules\Academic\Domain\Aggregates\Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: \Modules\Academic\Domain\ValueObjects\CourseCode::fromString('EXM-'.strtoupper((string) Str::random(4))),
        title: \Modules\Academic\Domain\ValueObjects\CourseTitle::fromString('Curso de examen'),
    );
    $courseRepository->save($course);

    $questionRepository = app(QuestionRepository::class);
    $competencyId = \Modules\Academic\Domain\ValueObjects\CompetencyId::fromString(persistedQuestionCompetencyId());
    $questionIds = [];
    foreach (['opt-a', 'opt-b'] as $refId) {
        $question = \Modules\Academic\Domain\Aggregates\Question::create(
            \Modules\Academic\Domain\ValueObjects\QuestionId::fromString((string) Str::uuid()),
            \Modules\Academic\Domain\Enums\QuestionType::SingleChoice,
            $competencyId,
            '¿Pregunta '.$refId.'?',
            1,
            \Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => $refId]),
            [
                \Modules\Academic\Domain\Entities\QuestionOption::create($refId, \Modules\Academic\Domain\ValueObjects\QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
                \Modules\Academic\Domain\Entities\QuestionOption::create('opt-b', \Modules\Academic\Domain\ValueObjects\QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
            ],
        );
        $questionRepository->save($question);
        $questionIds[] = $question->id()->value();
    }

    return [$course->id()->value(), $questionIds];
}

it('crea un examen exitosamente', function (): void {
    [$courseId, $questionIds] = persistedExamFixtures();
    $repository = new InMemoryExamRepository;
    $handler = new CreateExamHandler($repository, app(CourseRepository::class), app(QuestionRepository::class));

    $response = $handler->handle(new CreateExamCommand(
        courseId: $courseId,
        title: 'Examen final',
        maxAttempts: 2,
        passingScore: 70,
        questions: examQuestionPayloads($questionIds),
    ));

    expect($response)->toBeInstanceOf(ExamResponse::class)
        ->and($repository->saveCalls)->toBe(1)
        ->and($response->title)->toBe('Examen final')
        ->and($response->questions)->toHaveCount(2);
});

it('rechaza crear un examen con curso inexistente', function (): void {
    $repository = new InMemoryExamRepository;
    $handler = new CreateExamHandler($repository, app(CourseRepository::class), app(QuestionRepository::class));

    expect(fn () => $handler->handle(new CreateExamCommand(
        courseId: (string) Str::uuid(),
        title: 'Examen',
        questions: examQuestionPayloads([(string) Str::uuid()]),
    )))->toThrow(\Modules\Academic\Application\Exceptions\CourseNotFound::class)
        ->and($repository->saveCalls)->toBe(0);
});

it('rechaza crear un examen con pregunta inexistente', function (): void {
    [$courseId] = persistedExamFixtures();
    $repository = new InMemoryExamRepository;
    $handler = new CreateExamHandler($repository, app(CourseRepository::class), app(QuestionRepository::class));

    expect(fn () => $handler->handle(new CreateExamCommand(
        courseId: $courseId,
        title: 'Examen',
        questions: examQuestionPayloads([(string) Str::uuid()]),
    )))->toThrow(\Modules\Academic\Application\Exceptions\QuestionNotFound::class)
        ->and($repository->saveCalls)->toBe(0);
});

it('obtiene y lista exámenes filtrados por curso', function (): void {
    [$courseId, $questionIds] = persistedExamFixtures();
    $repository = new InMemoryExamRepository;
    $create = new CreateExamHandler($repository, app(CourseRepository::class), app(QuestionRepository::class));
    $create->handle(new CreateExamCommand(courseId: $courseId, title: 'Uno', questions: examQuestionPayloads($questionIds)));
    $create->handle(new CreateExamCommand(courseId: $courseId, title: 'Dos', questions: examQuestionPayloads($questionIds)));

    $all = (new ListExamsHandler($repository))->handle(new ListExamsQuery);
    expect($all)->toHaveCount(2);

    $filtered = (new ListExamsHandler($repository))->handle(new ListExamsQuery($courseId));
    expect($filtered)->toHaveCount(2)
        ->and($filtered[0])->toBeInstanceOf(\Modules\Academic\Application\Responses\ExamListItemResponse::class);

    $exam = $repository->exams[array_key_first($repository->exams)];
    $detail = (new GetExamHandler($repository))->handle(new GetExamQuery($exam->id()->value()));
    expect($detail)->toBeInstanceOf(ExamResponse::class);
});

it('actualiza y elimina un examen', function (): void {
    [$courseId, $questionIds] = persistedExamFixtures();
    $repository = new InMemoryExamRepository;
    $create = new CreateExamHandler($repository, app(CourseRepository::class), app(QuestionRepository::class));
    $created = $create->handle(new CreateExamCommand(courseId: $courseId, title: 'Antes', questions: examQuestionPayloads($questionIds)));

    $updated = (new UpdateExamHandler($repository, app(QuestionRepository::class)))->handle(new UpdateExamCommand(
        examId: $created->id,
        title: 'Después',
        maxAttempts: 3,
        questions: examQuestionPayloads($questionIds),
    ));
    expect($updated->title)->toBe('Después')
        ->and($updated->maxAttempts)->toBe(3);

    (new DeleteExamHandler($repository))->handle(new DeleteExamCommand($created->id));
    expect($repository->findById(ExamId::fromString($created->id)))->toBeNull();

    expect(fn () => (new GetExamHandler($repository))->handle(new GetExamQuery($created->id)))
        ->toThrow(ExamNotFound::class);
});

it('rechaza actualizar un examen inexistente', function (): void {
    $repository = new InMemoryExamRepository;
    $handler = new UpdateExamHandler($repository, app(QuestionRepository::class));

    expect(fn () => $handler->handle(new UpdateExamCommand(
        examId: (string) Str::uuid(),
        title: 'No existe',
        questions: examQuestionPayloads([(string) Str::uuid()]),
    )))->toThrow(ExamNotFound::class);
});
```

**Step 2: Run to verify fail**

Run: `... php artisan test modules/Academic/Tests/Unit/Application/ExamHandlerTest.php`
Expected: FAIL (clases no existen).

**Step 3: Write implementation**

`ExamResponse` (copia `QuestionResponse::fromQuestion`, pero `fromExam(Exam $exam, array $questionsByRefId)` donde `$questionsByRefId` es `map<string, array{refId: string, type: string}>` construido por el handler desde `QuestionRepository`). Forma `toArray()`:

```php
[
    'id' => string,
    'title' => string,
    'course_id' => string,
    'description' => string|null,
    'duration_minutes' => int|null,
    'max_attempts' => int,
    'passing_score' => int,
    'shuffle_questions' => bool,
    'feedback_mode' => string,
    'questions' => list<array{position: int, question_id: string, points: int, ref_id: string, type: string}>,
]
```

`ExamListItemResponse::fromExam(Exam $exam)` → `{id, title, course_id, question_count, passing_score}`.

`CreateExamHandler` (copia `CreateQuestionHandler`): inyecta `ExamRepository`, `CourseRepository`, `QuestionRepository`. Valida curso con `CourseNotFound::withId`, valida cada pregunta con `QuestionNotFound::withId`, construye preguntas del examen (posición 1..n, puntos), guarda y devuelve `ExamResponse` enriqueciendo ref_id/type consultando las preguntas.

`UpdateExamHandler`: `findById` → 404, valida preguntas, `replace(...)`, `save`, devuelve `ExamResponse`.

`DeleteExamHandler`: `findById` → 404, `delete`.

`GetExamHandler`: `findById` → 404, devuelve `ExamResponse` enriqueciendo.

`ListExamsHandler`: devuelve `list<ExamListItemResponse>` con `all(?courseId)`.

**Step 4: Run to verify pass**

Run: `... php artisan test modules/Academic/Tests/Unit/Application/ExamHandlerTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Application/Responses modules/Academic/Application/UseCases modules/Academic/Tests/Unit/Application/ExamHandlerTest.php
git commit -m "feat(academic): add exam application use cases and responses"
```

---

### Task 8: Persistencia Eloquent (TDD)

**Files:**
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/ExamModel.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/ExamQuestionModel.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentExamRepository.php`
- Test: `modules/Academic/Tests/Integration/EloquentExamRepositoryTest.php`

**Step 1: Write the failing tests**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ExamModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ExamQuestionModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentExamRepository;

/** @return list<ExamQuestion> */
function examRepoQuestions(array $questionIds): array
{
    return array_map(
        static fn (string $id, int $index): ExamQuestion => ExamQuestion::create($index + 1, QuestionId::fromString($id), 1),
        $questionIds,
        array_keys($questionIds),
    );
}

/** Persists a course and a question for exam repository tests, returning [courseId, questionIds]. */
function examRepoFixtures(): array
{
    $courseRepository = app(CourseRepository::class);
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('EXR-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso de examen'),
    );
    $courseRepository->save($course);

    $questionRepository = app(QuestionRepository::class);
    $competencyId = \Modules\Academic\Domain\ValueObjects\CompetencyId::fromString(persistedQuestionCompetencyId());
    $questionIds = [];
    foreach (['opt-a', 'opt-b'] as $refId) {
        $question = \Modules\Academic\Domain\Aggregates\Question::create(
            QuestionId::fromString((string) Str::uuid()),
            \Modules\Academic\Domain\Enums\QuestionType::SingleChoice,
            $competencyId,
            '¿Pregunta '.$refId.'?',
            1,
            \Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => $refId]),
            [
                \Modules\Academic\Domain\Entities\QuestionOption::create($refId, \Modules\Academic\Domain\ValueObjects\QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
                \Modules\Academic\Domain\Entities\QuestionOption::create('opt-b', \Modules\Academic\Domain\ValueObjects\QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
            ],
        );
        $questionRepository->save($question);
        $questionIds[] = $question->id()->value();
    }

    return [$course->id()->value(), $questionIds];
}

it('guarda y reconstruye un examen con sus preguntas ordenadas', function (): void {
    [$courseId, $questionIds] = examRepoFixtures();
    $repository = app(EloquentExamRepository::class);

    $exam = Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString($courseId),
        title: 'Examen integración',
        description: 'Descripción.',
        durationMinutes: 30,
        maxAttempts: 2,
        passingScore: 75,
        shuffleQuestions: true,
        feedbackMode: ExamFeedbackMode::AfterSubmission,
        questions: examRepoQuestions($questionIds),
    );
    $repository->save($exam);

    $stored = $repository->findById($exam->id());

    expect($stored)->not->toBeNull()
        ->and($stored?->title())->toBe('Examen integración')
        ->and($stored?->maxAttempts())->toBe(2)
        ->and($stored?->passingScore())->toBe(75)
        ->and($stored?->shuffleQuestions())->toBeTrue()
        ->and($stored?->feedbackMode())->toBe(ExamFeedbackMode::AfterSubmission)
        ->and($stored?->questions())->toHaveCount(2)
        ->and($stored?->questions()[0]->position())->toBe(1)
        ->and($stored?->questions()[1]->position())->toBe(2);
});

it('lista exámenes filtrados por curso', function (): void {
    [$courseId, $questionIds] = examRepoFixtures();
    $repository = app(EloquentExamRepository::class);
    $otherCourse = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('EXR-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Otro curso'),
    );
    app(CourseRepository::class)->save($otherCourse);

    $repository->save(Exam::create(ExamId::fromString((string) Str::uuid()), CourseId::fromString($courseId), 'Del curso', examRepoQuestions($questionIds)));
    $repository->save(Exam::create(ExamId::fromString((string) Str::uuid()), $otherCourse->id(), 'De otro curso', examRepoQuestions($questionIds)));

    $all = $repository->all();
    expect($all)->toHaveCount(2);

    $filtered = $repository->all(CourseId::fromString($courseId));
    expect($filtered)->toHaveCount(1)
        ->and($filtered[0]->title())->toBe('Del curso');
});

it('borra un examen y sus preguntas asociadas', function (): void {
    [$courseId, $questionIds] = examRepoFixtures();
    $repository = app(EloquentExamRepository::class);
    $exam = Exam::create(ExamId::fromString((string) Str::uuid()), CourseId::fromString($courseId), 'A borrar', examRepoQuestions($questionIds));
    $repository->save($exam);

    $repository->delete($exam->id());

    expect($repository->findById($exam->id()))->toBeNull()
        ->and(ExamQuestionModel::query()->where('exam_id', $exam->id()->value())->count())->toBe(0);
});
```

**Step 2: Run to verify fail**

Run: `... php artisan test modules/Academic/Tests/Integration/EloquentExamRepositoryTest.php`
Expected: FAIL (clases no existen).

**Step 3: Write implementation**

`ExamModel` (copia `QuestionModel`, tabla `academic_exams`), con `questions(): HasMany<ExamQuestionModel>` ordenado por `position`, casts `duration_minutes` int, `max_attempts` int, `passing_score` int, `shuffle_questions` bool, `feedback_mode` string.

`ExamQuestionModel` (copia `QuestionOptionModel`, tabla `academic_exam_questions`), casts `position` int, `points` int.

`EloquentExamRepository` (copia `EloquentQuestionRepository`): `save` con `updateOrCreate` + borrado de preguntas previas + re-inserción; `findById` con `with('questions')`; `all(?courseId)` con `when($courseId)`; `delete`. Reconstrucción vía `Exam::restore(...)` con `array_values` (patrón `array_values(...->all())` para satisfacer list). `findById`/`all` deben cargar preguntas sin N+1.

**Step 4: Run to verify pass**

Run: `... php artisan test modules/Academic/Tests/Integration/EloquentExamRepositoryTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Infrastructure/Persistence/Eloquent/Models/ExamModel.php modules/Academic/Infrastructure/Persistence/Eloquent/Models/ExamQuestionModel.php modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentExamRepository.php modules/Academic/Tests/Integration/EloquentExamRepositoryTest.php
git commit -m "feat(academic): add eloquent exam repository"
```

---

### Task 9: Registrar en el provider

**Files:**
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`

**Step 1: Add the bind and handler registrations**

En `register()` añadir (patrón del bind de `QuestionRepository`):

```php
$this->app->bind(
    ExamRepository::class,
    EloquentExamRepository::class,
);
```

En `boot()` añadir:

```php
$registry->register(CreateExamCommand::class, CreateExamHandler::class);
$registry->register(UpdateExamCommand::class, UpdateExamHandler::class);
$registry->register(DeleteExamCommand::class, DeleteExamHandler::class);
$registry->register(GetExamQuery::class, GetExamHandler::class);
$registry->register(ListExamsQuery::class, ListExamsHandler::class);
```

Y los `use` correspondientes (comandos, consultas, handlers, `ExamRepository`, `EloquentExamRepository`).

**Step 2: Smoke check**

Run: `... php artisan route:list --path=academic/exams`
Expected: no crash ("Your application doesn't have any routes matching..." es correcto).

**Step 3: Commit**

```bash
git add modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php
git commit -m "feat(academic): register exam handlers in provider"
```

---

### Task 10: Permisos y grants

**Files:**
- Modify: `modules/Authorization/Domain/Enums/Permission.php`
- Modify: `modules/Authorization/Domain/Services/RolePermissions.php`
- Modify: `modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`

**Step 1: Add permission cases**

```php
case ManageExams = 'exams.manage';
case ViewExams = 'exams.view';
```

**Step 2: Add grants**

- SuperAdmin: añadir ambos.
- InstitutionalAdmin/Teacher/Student: añadir `ViewExams`.

**Step 3: Update tests** (bloque igual al de preguntas, con `ManageExams`/`ViewExams`).

**Step 4: Run auth tests**

Run: `... php artisan test modules/Authorization`
Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Authorization/Domain/Enums/Permission.php modules/Authorization/Domain/Services/RolePermissions.php modules/Authorization/Tests
git commit -m "feat(authorization): add exam permissions"
```

---

### Task 11: Presentación HTTP (TDD)

**Files:**
- Create: `modules/Academic/Presentation/Http/Controllers/ExamController.php`
- Create: `modules/Academic/Presentation/Http/Requests/CreateExamRequest.php`
- Create: `modules/Academic/Presentation/Http/Requests/UpdateExamRequest.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Test: `modules/Academic/Tests/Feature/ExamTest.php`

**Step 1: Write the failing feature tests**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

/** @return list<array{question_id: string, points: int}> */
function examPayloadQuestions(array $questionIds): array
{
    return array_map(static fn (string $id): array => ['question_id' => $id, 'points' => 1], $questionIds);
}

function examPayload(string $courseId, array $questionIds, array $overrides = []): array
{
    return array_merge([
        'course_id' => $courseId,
        'title' => 'Examen teórico',
        'description' => 'Evaluación final.',
        'duration_minutes' => 45,
        'max_attempts' => 2,
        'passing_score' => 70,
        'shuffle_questions' => true,
        'feedback_mode' => 'after_submission',
        'questions' => examPayloadQuestions($questionIds),
    ], $overrides);
}

function persistedExamCourseId(): string
{
    return createDraftCourseForPublishing('EXM-'.strtoupper((string) Str::random(4)))->id()->value();
}

/** @return list<string> */
function persistedExamQuestionIds(): array
{
    $questionRepository = app(\Modules\Academic\Domain\Repositories\QuestionRepository::class);
    $competencyId = \Modules\Academic\Domain\ValueObjects\CompetencyId::fromString(persistedQuestionCompetencyId());
    $ids = [];
    foreach (['opt-a', 'opt-b'] as $refId) {
        $question = \Modules\Academic\Domain\Aggregates\Question::create(
            \Modules\Academic\Domain\ValueObjects\QuestionId::fromString((string) Str::uuid()),
            \Modules\Academic\Domain\Enums\QuestionType::SingleChoice,
            $competencyId,
            '¿Pregunta '.$refId.'?',
            1,
            \Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => $refId]),
            [
                \Modules\Academic\Domain\Entities\QuestionOption::create($refId, \Modules\Academic\Domain\ValueObjects\QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
                \Modules\Academic\Domain\Entities\QuestionOption::create('opt-b', \Modules\Academic\Domain\ValueObjects\QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
            ],
        );
        $questionRepository->save($question);
        $ids[] = $question->id()->value();
    }

    return $ids;
}

it('crea un examen con preguntas válidas', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), persistedExamQuestionIds()))
        ->assertCreated()
        ->assertJsonPath('data.title', 'Examen teórico')
        ->assertJsonPath('data.max_attempts', 2)
        ->assertJsonPath('data.passing_score', 70)
        ->assertJsonPath('data.shuffle_questions', true)
        ->assertJsonPath('data.feedback_mode', 'after_submission')
        ->assertJsonCount(2, 'data.questions')
        ->assertJsonStructure(['data' => ['id', 'title', 'course_id', 'questions']]);
});

it('rechaza crear un examen con curso inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/exams', examPayload((string) Str::uuid(), persistedExamQuestionIds()))
        ->assertNotFound()
        ->assertJsonPath('code', 'COURSE_NOT_FOUND');
});

it('rechaza crear un examen con pregunta inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), [(string) Str::uuid()]))
        ->assertNotFound()
        ->assertJsonPath('code', 'QUESTION_NOT_FOUND');
});

it('valida duración, intentos y puntaje fuera de rango', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $questionIds = persistedExamQuestionIds();

    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), $questionIds, ['duration_minutes' => 0]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['duration_minutes']);

    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), $questionIds, ['max_attempts' => 0]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['max_attempts']);

    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), $questionIds, ['passing_score' => 101]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['passing_score']);
});

it('rechaza un examen sin preguntas', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), [], ['questions' => []]))
        ->assertUnprocessable()
        ->assertJsonPath('code', 'INVALID_EXAM');
});

it('lista exámenes filtrados por curso', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $questionIds = persistedExamQuestionIds();

    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), $questionIds))->assertCreated();
    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), $questionIds))->assertCreated();

    $this->getJson('/api/v1/academic/exams')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson('/api/v1/academic/exams?course_id='.$courseId)
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('obtiene el detalle de un examen con sus preguntas en orden', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $created = $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), persistedExamQuestionIds()))
        ->assertCreated();

    $this->getJson('/api/v1/academic/exams/'.$created->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.questions.0.position', 1)
        ->assertJsonPath('data.questions.1.position', 2)
        ->assertJsonPath('data.questions.0.type', 'single_choice');
});

it('actualiza un examen', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $created = $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), persistedExamQuestionIds()))
        ->assertCreated();

    $this->putJson('/api/v1/academic/exams/'.$created->json('data.id'), [
        'title' => 'Examen actualizado',
        'max_attempts' => 3,
        'passing_score' => 80,
        'questions' => examPayloadQuestions(persistedExamQuestionIds()),
    ])->assertOk()
        ->assertJsonPath('data.title', 'Examen actualizado')
        ->assertJsonPath('data.max_attempts', 3)
        ->assertJsonPath('data.passing_score', 80);
});

it('elimina un examen y deja de listarlo', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $created = $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), persistedExamQuestionIds()))
        ->assertCreated();

    $this->deleteJson('/api/v1/academic/exams/'.$created->json('data.id'))->assertNoContent();

    $this->getJson('/api/v1/academic/exams')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('responde 404 para exámenes inexistentes en obtener, actualizar y eliminar', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $examId = (string) Str::uuid();

    $this->getJson("/api/v1/academic/exams/{$examId}")->assertNotFound()->assertJsonPath('code', 'EXAM_NOT_FOUND');
    $this->putJson("/api/v1/academic/exams/{$examId}", ['title' => 'X', 'questions' => examPayloadQuestions(persistedExamQuestionIds())])
        ->assertNotFound()->assertJsonPath('code', 'EXAM_NOT_FOUND');
    $this->deleteJson("/api/v1/academic/exams/{$examId}")->assertNotFound()->assertJsonPath('code', 'EXAM_NOT_FOUND');
});

it('protege los endpoints de exámenes con autenticación', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/academic/exams')->assertUnauthorized();
    $this->getJson('/api/v1/academic/exams/'.Str::uuid())->assertUnauthorized();
    $this->postJson('/api/v1/academic/exams', [])->assertUnauthorized();
    $this->putJson('/api/v1/academic/exams/'.Str::uuid(), [])->assertUnauthorized();
    $this->deleteJson('/api/v1/academic/exams/'.Str::uuid())->assertUnauthorized();
});

it('permite a un estudiante listar pero no crear exámenes', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);

    $this->getJson('/api/v1/academic/exams')->assertOk();
    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), persistedExamQuestionIds()))
        ->assertForbidden();
});
```

> Nota: en el test "lista exámenes filtrados por curso", capturar `$courseId = persistedExamCourseId();` antes de los dos POST y usar ese mismo id en el filtro (el helper crea un curso nuevo cada llamada).

**Step 2: Run to verify fail**

Run: `... php artisan test modules/Academic/Tests/Feature/ExamTest.php`
Expected: FAIL (404 por rutas inexistentes).

**Step 3: Implement requests, controller y rutas**

`CreateExamRequest` (copia `CreateQuestionRequest`): campos `course_id` (uuid required), `title` (required max:180), `description` (nullable max:2000), `duration_minutes` (nullable integer min:1), `max_attempts` (integer min:1), `passing_score` (integer min:1 max:100), `shuffle_questions` (boolean), `feedback_mode` (`new Enum(ExamFeedbackMode::class)`), `questions` (required array), `questions.*.question_id` (required uuid distinct), `questions.*.points` (required integer min:1). `authorize() => true`.

`UpdateExamRequest`: igual pero sin `course_id`.

`ExamController` (copia `QuestionController`): `index(Request $request, QueryBus)` con filtro `course_id`; `store` (201); `show(string $examId, QueryBus)`; `update(string $examId, UpdateExamRequest, CommandBus)`; `destroy(string $examId, CommandBus)` (204). Normalizar `questions.*.question_id` → `questionId` y `questions.*.points` → `points` vía `normalizeQuestions()` privado.

`Routes/api.php`: añadir `use Modules\Academic\Presentation\Http\Controllers\ExamController;` y los grupos:

```php
Route::middleware('permission:exams.view')->group(function (): void {
    Route::get('/exams', [ExamController::class, 'index'])
        ->name('exams.index');
    Route::get('/exams/{examId}', [ExamController::class, 'show'])
        ->whereUuid('examId')
        ->name('exams.show');
});

Route::middleware('permission:exams.manage')->group(function (): void {
    Route::post('/exams', [ExamController::class, 'store'])
        ->name('exams.store');
    Route::put('/exams/{examId}', [ExamController::class, 'update'])
        ->whereUuid('examId')
        ->name('exams.update');
    Route::delete('/exams/{examId}', [ExamController::class, 'destroy'])
        ->whereUuid('examId')
        ->name('exams.destroy');
});
```

**Step 4: Run to verify pass**

Run: `... php artisan test modules/Academic/Tests/Feature/ExamTest.php`
Expected: PASS.

**Step 5: Run full Academic suite**

Run: `... php artisan test modules/Academic`
Expected: all PASS.

**Step 6: Pint y commit**

Run: `... vendor/bin/pint modules/Academic/Presentation modules/Academic/Tests/Feature/ExamTest.php modules/Academic/Tests/Unit/Application/ExamHandlerTest.php modules/Academic/Tests/Integration/EloquentExamRepositoryTest.php`
Expected: FIXED (style issues corregidos) o vacío.

```bash
git add modules/Academic/Presentation modules/Academic/Tests/Feature/ExamTest.php
git commit -m "feat(academic): expose exams http api"
```

---

### Task 12: Validación final (Pint, PHPStan, suite completa, migrate, route:list)

**Files:** (ninguno nuevo)

**Step 1: Pint**

Run: `... vendor/bin/pint`
Expected: FIXED (vacía o style issues corregidos).

**Step 2: PHPStan**

Run: `... vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
Expected: `[OK] No errors`.

**Step 3: Suite completa**

Run:
1. `... php artisan test` (root)
2. `... php artisan test modules/Academic`
3. `... php artisan test modules/Authorization modules/Identity modules/Organization modules/Audit modules/Foundation`

Expected: all PASS, sin FAIL. Corregir lo que PHPStan/Pint señalen en los archivos del incremento.

**Step 4: migrate + migrate:status sobre dev DB**

Run: `... php artisan migrate --force` y luego `... php artisan migrate:status`
Expected: `academic_exams` y `academic_exam_questions` con estado `Ran`.

**Step 5: route:list**

Run: `... php artisan route:list --path=academic/exams`
Expected: 5 rutas (index, store, show, update, destroy en `api/v1/academic/exams`).

**Step 6: Commit cualquier corrección de estilo/análisis**

```bash
git add -A modules/Academic tests modules/Authorization
git commit -m "style(academic): apply pint and phpstan fixes for exams"
```

---

### Task 13: Documentación (roadmap + ENG-LOG)

**Files:**
- Modify: `docs/roadmap/ENG-000-roadmap-tecnico-backend.md`
- Modify: `docs/engineering/ENG-LOG.md`

**Step 1: Roadmap** — marcar ENG-031 "Completado" en la sección 12 (Fase 6 — Evaluaciones) con nota similar a ENG-030, "Actualizado 2026-08-11" en la sección de historia activa, y entrada de changelog `1.11.0` en "Control de cambios".

**Step 2: ENG-LOG** — entrada `## 2026-08-11 — IMP-031 (Cierre de ENG-031 — Exámenes y cuestionarios)` con secciones Completado / Validaciones / Estado: Finalizado. Validaciones: pint, phpstan, nº total de pruebas/aserciones, counts de rutas (5) y migraciones (2 tablas).

**Step 3: Commit**

```bash
git add docs/roadmap/ENG-000-roadmap-tecnico-backend.md docs/engineering/ENG-LOG.md
git commit -m "docs(engineering): update roadmap and log for ENG-031 completion"
```

---

## Notas de riesgos y decisiones

- **`question_id` en requests:** el request usa `question_id` (snake_case) y el dominio `questionId`; el controller normaliza (patrón ya usado en ENG-030 con `ref_id` → `refId`).
- **Ref_id/type en el detalle:** `ExamResponse` enriquece cada pregunta con `ref_id`/`type` consultando `QuestionRepository` (sin acoplar el agregado al banco). El listado (`ExamListItemResponse`) omite preguntas.
- **`points` por pregunta:** el agregado permite puntaje distinto por pregunta; `passing_score` es porcentaje (1–100). Interpretación en calificación diferida a ENG-033.
- **Cascade:** `ON DELETE CASCADE` en `academic_exams.course_id` y `academic_exam_questions.exam_id/question_id` (consistente con ENG-030).
- **Sin N+1:** el repo carga preguntas del examen con eager loading y `array_values(...->all())` para satisfacer list.
- **Validación de posiciones:** el agregado exige posiciones secuenciales 1..n; el handler las asigna por índice de la lista entrante.
- **Verificación cruzada:** tests del módulo Academic siempre verdes (`php artisan test modules/Academic`); Pint antes de cada commit si se tocó PHP; PHPStan sin errores nivel 8.
