# ENG-032 — Intentos de evaluación: Plan de implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implementar la ejecución de intentos de examen sobre el agregado `Exam` de ENG-031: inicio con snapshot inmutable, respuestas por pregunta, guardado progresivo, finalización con resultado básico (score/percentage/passed) y prevención de duplicados. Solo definición y ejecución del intento; la calificación fina (ENG-033) y el examen teórico (ENG-034) quedan diferidos.

**Architecture:** Hexagonal dentro de `Modules\Academic` (Domain → Application → Infrastructure → Presentation), CQRS vía `CommandBus`/`QueryBus` y `MessageHandlerRegistry`. El agregado `ExamAttempt` es el núcleo: snapshot inmutable del examen (configuración + preguntas con prompt/options/correct_response/explanation) generado al iniciar, respuestas tipadas reutilizando las de ENG-030 (con nuevo método `matches()`), estados `in_progress`/`submitted`/`canceled`. Persistencia en 2 tablas `academic_exam_attempts` + `academic_exam_attempt_questions`. API HTTP bajo `auth:sanctum` con regla dueño-o-permiso (`exam_attempts.view`).

**Tech Stack:** PHP 8.2, Laravel 12, PostgreSQL (prod) / SQLite :memory: (tests), Pest, PHPStan nivel 8, Pint.

---

**Referencias de patrón (ENG-031, ya commiteados):** `Exam.php`, `ExamQuestion.php`, `ExamId.php`, `InvalidExam.php`, `ExamRepository.php`, `EloquentExamRepository.php`, `ExamModel.php`, `ExamQuestionModel.php`, `CreateExamHandler.php`, `GetExamHandler.php`, `ExamResponse.php`, `ExamController.php`, `CreateExamRequest.php`, `api.php`, `AcademicServiceProvider.php`. Respuestas tipadas ENG-030: `SingleChoiceResponse`, `MultiSelectResponse`, `TrueFalseResponse`, `MatchingResponse`, `OrderingResponse`, `QuestionResponse` (interface), `QuestionResponseFactory`. Helpers de tests en `tests/Pest.php`: `actingAsRole`, `actingAsAuthenticatedUser`, `persistedQuestionCompetencyId`, `createDraftCourseForPublishing`.

**CLI (siempre en contenedor desechable — la imagen fija monta copia obsoleta):**
```
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan <cmd>
```
- PHPStan: `vendor/bin/phpstan analyse --no-progress --memory-limit=1G` (lento, timeout generoso)
- Pint: `vendor/bin/pint` antes de cada commit
- Tests: `php artisan test <archivo>` o suite completa
- Errores de dominio extienden `Modules\Foundation\Domain\Exceptions\DomainException`; los mapea `bootstrap/app.php`.
- Windows: si aparece un archivo `nul`, borrarlo (`rm -f nul`).

---

### Task 1: Migración de intentos de evaluación

**Files:**
- Create: `modules/Academic/Infrastructure/Persistence/Migrations/2026_08_12_000001_create_academic_exam_attempt_tables.php`

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
        Schema::create('academic_exam_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_id')->constrained('academic_exams')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20);
            $table->dateTimeTz('started_at');
            $table->dateTimeTz('submitted_at')->nullable();
            $table->string('title', 180);
            $table->integer('duration_minutes')->nullable();
            $table->smallInteger('passing_score');
            $table->boolean('shuffle_questions');
            $table->string('feedback_mode', 20);
            $table->integer('score')->default(0);
            $table->integer('total_points')->default(0);
            $table->integer('percentage')->default(0);
            $table->boolean('passed')->default(false);
            $table->timestampsTz();
        });

        Schema::create('academic_exam_attempt_questions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('attempt_id')->constrained('academic_exam_attempts')->cascadeOnDelete();
            $table->integer('position');
            $table->foreignUuid('question_id')->constrained('academic_questions');
            $table->integer('points');
            $table->text('prompt');
            $table->string('type', 20);
            $table->jsonb('options')->nullable();
            $table->jsonb('correct_response');
            $table->text('explanation')->nullable();
            $table->jsonb('user_response')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->dateTimeTz('answered_at')->nullable();
            $table->timestampsTz();
            $table->unique(['attempt_id', 'position']);
            $table->unique(['attempt_id', 'question_id']);
        });

        $sql = 'CREATE UNIQUE INDEX academic_exam_attempts_active_unique '
            .'ON academic_exam_attempts (exam_id, user_id) '
            ."WHERE status = 'in_progress'";
        DB::statement($sql);
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_exam_attempt_questions');
        Schema::dropIfExists('academic_exam_attempts');
    }
};
```

**Step 2: Run migration in both drivers**

Run: `php artisan migrate --force`
Expected: `create_academic_exam_attempt_tables` migrates.

Run (verify SQLite test DB also builds): `php artisan test modules/Foundation/Tests/Unit/DomainExceptionTest.php`
Expected: PASS (proves `RefreshDatabase` can rebuild schema on SQLite in-memory without index errors).

**Step 3: Commit**

```bash
git add modules/Academic/Infrastructure/Persistence/Migrations/2026_08_12_000001_create_academic_exam_attempt_tables.php
git commit -m "feat(academic): add exam attempts tables migration"
```

---

### Task 2: Value objects, enum y excepción de dominio del intento

**Files:**
- Create: `modules/Academic/Domain/ValueObjects/ExamAttemptId.php`
- Create: `modules/Academic/Domain/ValueObjects/AttemptQuestionId.php`
- Create: `modules/Academic/Domain/Enums/ExamAttemptStatus.php`
- Create: `modules/Academic/Domain/Exceptions/InvalidExamAttempt.php`
- Test: `modules/Academic/Tests/Unit/Domain/ValueObjects/ExamAttemptIdTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Modules\Academic\Domain\ValueObjects\ExamAttemptId;

it('construye un ExamAttemptId desde un UUID válido', function (): void {
    $id = ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92001');
    expect($id->value())->toBe('01981a64-8300-7b1d-b442-764ea7f92001');
});

it('rechaza un ExamAttemptId no UUID', function (): void {
    expect(fn () => ExamAttemptId::fromString('no-uuid'))->toThrow(InvalidArgumentException::class);
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/ValueObjects/ExamAttemptIdTest.php`
Expected: FAIL (class not found).

**Step 3: Write implementation** (copia exacta del patrón de `ExamId.php`)

`modules/Academic/Domain/ValueObjects/ExamAttemptId.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class ExamAttemptId
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $value) !== 1) {
            throw new InvalidArgumentException('El identificador del intento debe ser un UUID válido.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

`modules/Academic/Domain/ValueObjects/AttemptQuestionId.php`: idéntico pero mensaje "El identificador de la pregunta del intento debe ser un UUID válido."

`modules/Academic/Domain/Enums/ExamAttemptStatus.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum ExamAttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Canceled = 'canceled';
}
```

`modules/Academic/Domain/Exceptions/InvalidExamAttempt.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidExamAttempt extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El intento de evaluación no es válido.',
            errorCode: 'INVALID_EXAM_ATTEMPT',
            statusCode: 422,
        );
    }
}
```

**Step 4: Run test to verify it passes**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/ValueObjects/ExamAttemptIdTest.php`
Expected: PASS (2 tests).

**Step 5: Commit**

```bash
git add modules/Academic/Domain/ValueObjects/ExamAttemptId.php modules/Academic/Domain/ValueObjects/AttemptQuestionId.php modules/Academic/Domain/Enums/ExamAttemptStatus.php modules/Academic/Domain/Exceptions/InvalidExamAttempt.php modules/Academic/Tests/Unit/Domain/ValueObjects/ExamAttemptIdTest.php
git commit -m "feat(academic): add exam attempt id, status enum and invalid exception"
```

---

### Task 3: `matches()` en las respuestas tipadas (ENG-030)

**Files:**
- Modify: `modules/Academic/Domain/Entities/Responses/QuestionResponse.php` (interface, +`matches`)
- Modify: `modules/Academic/Domain/Entities/Responses/SingleChoiceResponse.php`
- Modify: `modules/Academic/Domain/Entities/Responses/MultiSelectResponse.php`
- Modify: `modules/Academic/Domain/Entities/Responses/TrueFalseResponse.php`
- Modify: `modules/Academic/Domain/Entities/Responses/MatchingResponse.php`
- Modify: `modules/Academic/Domain/Entities/Responses/OrderingResponse.php`
- Test: `modules/Academic/Tests/Unit/Domain/Entities/QuestionResponseTest.php`

**Step 1: Write the failing tests** (añadir al final de `QuestionResponseTest.php`)

```php
it('compara respuestas de selección única', function (): void {
    $correct = SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']);
    expect($correct->matches(SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a'])))->toBeTrue()
        ->and($correct->matches(SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-b'])))->toBeFalse();
});

it('compara respuestas de selección múltiple como conjunto', function (): void {
    $correct = MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['a', 'b']]);
    expect($correct->matches(MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['b', 'a']])))->toBeTrue()
        ->and($correct->matches(MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['a', 'c']])))->toBeFalse();
});

it('compara respuestas verdadero/falso', function (): void {
    $correct = TrueFalseResponse::fromArray(['type' => 'true_false', 'correct' => true]);
    expect($correct->matches(TrueFalseResponse::fromArray(['type' => 'true_false', 'correct' => true])))->toBeTrue()
        ->and($correct->matches(TrueFalseResponse::fromArray(['type' => 'true_false', 'correct' => false])))->toBeFalse();
});

it('compara respuestas de asociación sin importar el orden de pares', function (): void {
    $correct = MatchingResponse::fromArray(['type' => 'matching', 'pairs' => [
        ['leftId' => 'l1', 'rightId' => 'r1'],
        ['leftId' => 'l2', 'rightId' => 'r2'],
    ]]);
    expect($correct->matches(MatchingResponse::fromArray(['type' => 'matching', 'pairs' => [
        ['leftId' => 'l2', 'rightId' => 'r2'],
        ['leftId' => 'l1', 'rightId' => 'r1'],
    ]])))->toBeTrue()
        ->and($correct->matches(MatchingResponse::fromArray(['type' => 'matching', 'pairs' => [
            ['leftId' => 'l1', 'rightId' => 'r1'],
            ['leftId' => 'l2', 'rightId' => 'r9'],
        ]])))->toBeFalse();
});

it('compara respuestas de ordenamiento por orden exacto', function (): void {
    $correct = OrderingResponse::fromArray(['type' => 'ordering', 'itemIds' => ['a', 'b', 'c']]);
    expect($correct->matches(OrderingResponse::fromArray(['type' => 'ordering', 'itemIds' => ['a', 'b', 'c']])))->toBeTrue()
        ->and($correct->matches(OrderingResponse::fromArray(['type' => 'ordering', 'itemIds' => ['a', 'c', 'b']])))->toBeFalse();
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/Entities/QuestionResponseTest.php`
Expected: FAIL (`Call to undefined method ... matches()`).

**Step 3: Implement `matches()`**

En la interface `QuestionResponse.php` añadir:

```php
/** Forma canónica serializable de la respuesta correcta de una pregunta. */
interface QuestionResponse
{
    /** @return array<string, mixed> */
    public function toArray(): array;

    public function matches(self $other): bool;
}
```

`SingleChoiceResponse.php` — añadir método:

```php
    public function matches(QuestionResponse $other): bool
    {
        return $other instanceof self && $this->optionId === $other->optionId;
    }
```

`TrueFalseResponse.php`:

```php
    public function matches(QuestionResponse $other): bool
    {
        return $other instanceof self && $this->correct === $other->correct;
    }
```

`MultiSelectResponse.php`:

```php
    public function matches(QuestionResponse $other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        sort($this->optionIds);
        $otherIds = $other->optionIds;
        sort($otherIds);

        return $this->optionIds === $otherIds;
    }
```

`MatchingResponse.php`:

```php
    public function matches(QuestionResponse $other): bool
    {
        if (! $other instanceof self || count($this->pairs) !== count($other->pairs)) {
            return false;
        }

        return $this->normalizedPairs($this->pairs) === $this->normalizedPairs($other->pairs);
    }

    /** @param  list<array{leftId: string, rightId: string}>  $pairs
     *  @return array<int, string> */
    private static function normalizedPairs(array $pairs): array
    {
        $normalized = array_map(
            static fn (array $pair): string => $pair['leftId'].':'.$pair['rightId'],
            $pairs,
        );
        sort($normalized);

        return $normalized;
    }
```

`OrderingResponse.php`:

```php
    public function matches(QuestionResponse $other): bool
    {
        return $other instanceof self && $this->itemIds === $other->itemIds;
    }
```

Nota: `MultiSelectResponse` y `OrderingResponse` son `readonly`, por lo que `sort($this->optionIds)` sobre una propiedad readonly local no muta el objeto (PHP 8.2 copia el array por valor al llamar a `sort`). Para evitar ambigüedad de PHPStan, usar una variable local en lugar de mutar la propiedad:

`MultiSelectResponse.php`:

```php
    public function matches(QuestionResponse $other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        $mine = $this->optionIds;
        $theirs = $other->optionIds;
        sort($mine);
        sort($theirs);

        return $mine === $theirs;
    }
```

**Step 4: Run tests to verify they pass**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/Entities/QuestionResponseTest.php`
Expected: PASS (todos, incluidos los existentes).

**Step 5: Run PHPStan y Pint**

Run: `vendor/bin/phpstan analyse --no-progress --memory-limit=1G modules/Academic/Domain/Entities/Responses`
Expected: `[OK] No errors`.

Run: `vendor/bin/pint modules/Academic/Domain/Entities/Responses`
Expected: arregla formato.

**Step 6: Commit**

```bash
git add modules/Academic/Domain/Entities/Responses/ modules/Academic/Tests/Unit/Domain/Entities/QuestionResponseTest.php
git commit -m "feat(academic): add matches() to typed question responses"
```

---

### Task 4: Entidad `AttemptQuestion`

**Files:**
- Create: `modules/Academic/Domain/Entities/AttemptQuestion.php`
- Test: `modules/Academic/Tests/Unit/Domain/Entities/AttemptQuestionTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidExamAttempt;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionId;

it('construye una pregunta del intento con snapshot', function (): void {
    $attemptQuestion = AttemptQuestion::create(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
        1,
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
        10,
        '¿Pregunta?',
        QuestionType::SingleChoice,
        [['refId' => 'opt-a', 'id' => '01981a64-8300-7b1d-b442-764ea7f92103', 'label' => 'A', 'position' => 1, 'side' => null]],
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        'Explicación',
    );

    expect($attemptQuestion->position())->toBe(1)
        ->and($attemptQuestion->points())->toBe(10)
        ->and($attemptQuestion->type())->toBe(QuestionType::SingleChoice)
        ->and($attemptQuestion->correctResponse())->toBeInstanceOf(SingleChoiceResponse::class)
        ->and($attemptQuestion->userResponse())->toBeNull()
        ->and($attemptQuestion->isCorrect())->toBeNull();
});

it('rechaza posiciones y puntos inválidos en una pregunta del intento', function (): void {
    expect(fn () => AttemptQuestion::create(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
        0,
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
        1,
        'P',
        QuestionType::SingleChoice,
        [],
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    ))->toThrow(InvalidExamAttempt::class);

    expect(fn () => AttemptQuestion::create(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
        1,
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
        0,
        'P',
        QuestionType::SingleChoice,
        [],
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    ))->toThrow(InvalidExamAttempt::class);
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/Entities/AttemptQuestionTest.php`
Expected: FAIL (class not found).

**Step 3: Write implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use Modules\Academic\Domain\Entities\Responses\QuestionResponse;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidExamAttempt;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionId;

final class AttemptQuestion
{
    /** @param  list<array<string, mixed>>  $options */
    private function __construct(
        private AttemptQuestionId $id,
        private int $position,
        private QuestionId $questionId,
        private int $points,
        private string $prompt,
        private QuestionType $type,
        private array $options,
        private QuestionResponse $correctResponse,
        private ?string $explanation,
        private ?QuestionResponse $userResponse,
        private ?bool $isCorrect,
        private ?\DateTimeImmutable $answeredAt,
    ) {}

    /** @param  list<array<string, mixed>>  $options */
    public static function create(
        AttemptQuestionId $id,
        int $position,
        QuestionId $questionId,
        int $points,
        string $prompt,
        QuestionType $type,
        array $options,
        QuestionResponse $correctResponse,
        ?string $explanation = null,
        ?QuestionResponse $userResponse = null,
        ?bool $isCorrect = null,
        ?\DateTimeImmutable $answeredAt = null,
    ): self {
        if ($position < 1 || $points < 1 || trim($prompt) === '') {
            throw InvalidExamAttempt::create();
        }

        return new self($id, $position, $questionId, $points, trim($prompt), $type, $options, $correctResponse, $explanation, $userResponse, $isCorrect, $answeredAt);
    }

    /** @param  list<array<string, mixed>>  $options */
    public static function restore(
        AttemptQuestionId $id,
        int $position,
        QuestionId $questionId,
        int $points,
        string $prompt,
        QuestionType $type,
        array $options,
        QuestionResponse $correctResponse,
        ?string $explanation = null,
        ?QuestionResponse $userResponse = null,
        ?bool $isCorrect = null,
        ?\DateTimeImmutable $answeredAt = null,
    ): self {
        return self::create($id, $position, $questionId, $points, $prompt, $type, $options, $correctResponse, $explanation, $userResponse, $isCorrect, $answeredAt);
    }

    public function id(): AttemptQuestionId
    {
        return $this->id;
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

    public function prompt(): string
    {
        return $this->prompt;
    }

    public function type(): QuestionType
    {
        return $this->type;
    }

    /** @return list<array<string, mixed>> */
    public function options(): array
    {
        return $this->options;
    }

    public function correctResponse(): QuestionResponse
    {
        return $this->correctResponse;
    }

    public function explanation(): ?string
    {
        return $this->explanation;
    }

    public function userResponse(): ?QuestionResponse
    {
        return $this->userResponse;
    }

    public function isCorrect(): ?bool
    {
        return $this->isCorrect;
    }

    public function answeredAt(): ?\DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function answered(): bool
    {
        return $this->userResponse !== null;
    }
}
```

**Step 4: Run test to verify it passes**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/Entities/AttemptQuestionTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Domain/Entities/AttemptQuestion.php modules/Academic/Tests/Unit/Domain/Entities/AttemptQuestionTest.php
git commit -m "feat(academic): add exam attempt question entity"
```

---

### Task 5: Agregado `ExamAttempt` (TDD)

**Files:**
- Create: `modules/Academic/Domain/Aggregates/ExamAttempt.php`
- Test: `modules/Academic/Tests/Unit/Domain/Aggregates/ExamAttemptTest.php`

**Step 1: Write the failing tests**

```php
<?php

declare(strict_types=1);

use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidExamAttempt;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\QuestionId;

function attemptQuestions(): array
{
    return [
        AttemptQuestion::create(
            AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
            1,
            QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
            10,
            '¿Primera?',
            QuestionType::SingleChoice,
            [],
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        ),
        AttemptQuestion::create(
            AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92103'),
            2,
            QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92104'),
            10,
            '¿Segunda?',
            QuestionType::SingleChoice,
            [],
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-b']),
        ),
    ];
}

it('inicia un intento en estado in_progress con su snapshot', function (): void {
    $startedAt = new DateTimeImmutable('2026-08-12 10:00:00');
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen final',
        45,
        70,
        false,
        ExamFeedbackMode::AfterSubmission,
        attemptQuestions(),
        $startedAt,
    );

    expect($attempt->id()->value())->toBe('01981a64-8300-7b1d-b442-764ea7f92105')
        ->and($attempt->status())->toBe(ExamAttemptStatus::InProgress)
        ->and($attempt->startedAt())->toBe($startedAt)
        ->and($attempt->submittedAt())->toBeNull()
        ->and($attempt->title())->toBe('Examen final')
        ->and($attempt->durationMinutes())->toBe(45)
        ->and($attempt->passingScore())->toBe(70)
        ->and($attempt->feedbackMode())->toBe(ExamFeedbackMode::AfterSubmission)
        ->and($attempt->questions())->toHaveCount(2)
        ->and($attempt->totalPoints())->toBe(20)
        ->and($attempt->score())->toBe(0)
        ->and($attempt->percentage())->toBe(0)
        ->and($attempt->passed())->toBeFalse();
});

it('baraja el orden de las preguntas cuando shuffle_questions está activo', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        45,
        70,
        true,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
        shuffler: static fn (array $questions): array => array_reverse($questions),
    );

    $questions = $attempt->questions();
    expect($questions)->toHaveCount(2)
        ->and($questions[0]->position())->toBe(1)
        ->and($questions[1]->position())->toBe(2)
        ->and($questions[0]->questionId()->value())->toBe('01981a64-8300-7b1d-b442-764ea7f92104')
        ->and($questions[1]->questionId()->value())->toBe('01981a64-8300-7b1d-b442-764ea7f92102');
});

it('responde una pregunta y calcula el acierto', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );

    $attempt->answer(
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        new DateTimeImmutable('2026-08-12 10:01:00'),
    );

    expect($attempt->questions()[0]->userResponse())->not->toBeNull()
        ->and($attempt->questions()[0]->isCorrect())->toBeTrue();
});

it('sobrescribe una respuesta previa mientras está in_progress', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );

    $attempt->answer(1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-b']), new DateTimeImmutable('2026-08-12 10:01:00'));
    $attempt->answer(1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']), new DateTimeImmutable('2026-08-12 10:02:00'));

    expect($attempt->questions()[0]->isCorrect())->toBeTrue();
});

it('rechaza responder fuera de posición o con tipo incorrecto', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );

    expect(fn () => $attempt->answer(99, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']), new DateTimeImmutable('2026-08-12 10:01:00')))
        ->toThrow(InvalidExamAttempt::class);
});

it('envía el intento y calcula score, percentage y passed', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );

    $attempt->answer(1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']), new DateTimeImmutable('2026-08-12 10:01:00'));
    $attempt->answer(2, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-b']), new DateTimeImmutable('2026-08-12 10:02:00'));

    $attempt->submit(new DateTimeImmutable('2026-08-12 10:10:00'));

    expect($attempt->status())->toBe(ExamAttemptStatus::Submitted)
        ->and($attempt->submittedAt())->not->toBeNull()
        ->and($attempt->score())->toBe(20)
        ->and($attempt->percentage())->toBe(100)
        ->and($attempt->passed())->toBeTrue();
});

it('marca como no aprobado cuando no alcanza el passing_score', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        45,
        90,
        false,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );

    $attempt->answer(1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']), new DateTimeImmutable('2026-08-12 10:01:00'));

    $attempt->submit(new DateTimeImmutable('2026-08-12 10:10:00'));

    expect($attempt->score())->toBe(10)
        ->and($attempt->percentage())->toBe(50)
        ->and($attempt->passed())->toBeFalse();
});

it('cancela el intento cuando se envía después del tiempo límite', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        5,
        70,
        false,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );

    $attempt->submit(new DateTimeImmutable('2026-08-12 10:10:00'));

    expect($attempt->status())->toBe(ExamAttemptStatus::Canceled)
        ->and($attempt->score())->toBe(0);
});

it('rechaza responder o enviar un intento ya finalizado', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );
    $attempt->submit(new DateTimeImmutable('2026-08-12 10:10:00'));

    expect(fn () => $attempt->answer(1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']), new DateTimeImmutable('2026-08-12 10:11:00')))
        ->toThrow(InvalidExamAttempt::class)
        ->and(fn () => $attempt->submit(new DateTimeImmutable('2026-08-12 10:12:00')))
        ->toThrow(InvalidExamAttempt::class);
});

it('cancela un intento in_progress', function (): void {
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92105'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92106'),
        'user-1',
        'Examen',
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptQuestions(),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );

    $attempt->cancel();

    expect($attempt->status())->toBe(ExamAttemptStatus::Canceled);
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/ExamAttemptTest.php`
Expected: FAIL (class not found).

**Step 3: Write implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\Responses\QuestionResponse;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Exceptions\InvalidExamAttempt;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;

final class ExamAttempt
{
    /**
     * @param  list<AttemptQuestion>  $questions
     * @param  (callable(list<AttemptQuestion>): list<AttemptQuestion>)|null  $shuffler
     */
    private function __construct(
        private ExamAttemptId $id,
        private ExamId $examId,
        private string $userId,
        private ExamAttemptStatus $status,
        private DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $submittedAt,
        private string $title,
        private ?int $durationMinutes,
        private int $passingScore,
        private bool $shuffleQuestions,
        private ExamFeedbackMode $feedbackMode,
        private array $questions,
        private int $score,
        private int $totalPoints,
        private int $percentage,
        private bool $passed,
    ) {}

    /**
     * @param  list<AttemptQuestion>  $questions
     * @param  (callable(list<AttemptQuestion>): list<AttemptQuestion>)|null  $shuffler
     */
    public static function start(
        ExamAttemptId $id,
        ExamId $examId,
        string $userId,
        string $title,
        ?int $durationMinutes,
        int $passingScore,
        bool $shuffleQuestions,
        ExamFeedbackMode $feedbackMode,
        array $questions,
        DateTimeImmutable $startedAt,
        ?callable $shuffler = null,
    ): self {
        if ($shuffleQuestions) {
            $questions = ($shuffler ?? static fn (array $items): array => array_values(shuffle($items) ? $items : $items))($questions);
            $questions = array_values(array_map(
                static fn (AttemptQuestion $question, int $index): AttemptQuestion => $question->withPosition($index + 1),
                $questions,
                array_keys($questions),
            ));
        }

        $totalPoints = array_sum(array_map(static fn (AttemptQuestion $question): int => $question->points(), $questions));

        $attempt = new self(
            $id,
            $examId,
            $userId,
            ExamAttemptStatus::InProgress,
            $startedAt,
            null,
            trim($title),
            $durationMinutes,
            $passingScore,
            $shuffleQuestions,
            $feedbackMode,
            $questions,
            0,
            $totalPoints,
            0,
            false,
        );
        $attempt->assertValid();

        return $attempt;
    }

    /**
     * @param  list<AttemptQuestion>  $questions
     */
    public static function restore(
        ExamAttemptId $id,
        ExamId $examId,
        string $userId,
        ExamAttemptStatus $status,
        DateTimeImmutable $startedAt,
        ?DateTimeImmutable $submittedAt,
        string $title,
        ?int $durationMinutes,
        int $passingScore,
        bool $shuffleQuestions,
        ExamFeedbackMode $feedbackMode,
        array $questions,
        int $score,
        int $totalPoints,
        int $percentage,
        bool $passed,
    ): self {
        return new self($id, $examId, $userId, $status, $startedAt, $submittedAt, $title, $durationMinutes, $passingScore, $shuffleQuestions, $feedbackMode, $questions, $score, $totalPoints, $percentage, $passed);
    }

    public function answer(int $position, QuestionResponse $response, DateTimeImmutable $answeredAt): void
    {
        if ($this->status !== ExamAttemptStatus::InProgress) {
            throw InvalidExamAttempt::create();
        }

        $question = $this->questionAt($position);
        if ($question === null) {
            throw InvalidExamAttempt::create();
        }

        $question->answer($response, $answeredAt);
    }

    public function submit(DateTimeImmutable $submittedAt): void
    {
        if ($this->status !== ExamAttemptStatus::InProgress) {
            throw InvalidExamAttempt::create();
        }

        if ($this->durationMinutes !== null
            && $submittedAt->getTimestamp() > $this->startedAt->getTimestamp() + $this->durationMinutes * 60
        ) {
            $this->status = ExamAttemptStatus::Canceled;
            $this->submittedAt = $submittedAt;

            return;
        }

        $score = 0;
        foreach ($this->questions as $question) {
            if ($question->isCorrect() === true) {
                $score += $question->points();
            }
        }
        $percentage = $this->totalPoints > 0 ? (int) round($score / $this->totalPoints * 100) : 0;

        $this->status = ExamAttemptStatus::Submitted;
        $this->submittedAt = $submittedAt;
        $this->score = $score;
        $this->percentage = $percentage;
        $this->passed = $percentage >= $this->passingScore;
    }

    public function cancel(): void
    {
        if ($this->status !== ExamAttemptStatus::InProgress) {
            throw InvalidExamAttempt::create();
        }

        $this->status = ExamAttemptStatus::Canceled;
    }

    public function id(): ExamAttemptId
    {
        return $this->id;
    }

    public function examId(): ExamId
    {
        return $this->examId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function status(): ExamAttemptStatus
    {
        return $this->status;
    }

    public function startedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function submittedAt(): ?DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function durationMinutes(): ?int
    {
        return $this->durationMinutes;
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

    /** @return list<AttemptQuestion> */
    public function questions(): array
    {
        return $this->questions;
    }

    public function score(): int
    {
        return $this->score;
    }

    public function totalPoints(): int
    {
        return $this->totalPoints;
    }

    public function percentage(): int
    {
        return $this->percentage;
    }

    public function passed(): bool
    {
        return $this->passed;
    }

    public function questionAt(int $position): ?AttemptQuestion
    {
        foreach ($this->questions as $question) {
            if ($question->position() === $position) {
                return $question;
            }
        }

        return null;
    }

    private function assertValid(): void
    {
        if ($this->title === '' || $this->totalPoints < 1 || $this->questions === []) {
            throw InvalidExamAttempt::create();
        }
    }
}
```

Necesario: añadir a `AttemptQuestion` el método `withPosition(int $position): self` y `answer(QuestionResponse $response, DateTimeImmutable $answeredAt): void` (actualiza `userResponse`, calcula `isCorrect` con `matches()`, setea `answeredAt`).

Añadir en `AttemptQuestion.php`:

```php
    public function withPosition(int $position): self
    {
        if ($position < 1) {
            throw InvalidExamAttempt::create();
        }

        return new self($this->id, $position, $this->questionId, $this->points, $this->prompt, $this->type, $this->options, $this->correctResponse, $this->explanation, $this->userResponse, $this->isCorrect, $this->answeredAt);
    }

    public function answer(QuestionResponse $response, \DateTimeImmutable $answeredAt): void
    {
        $this->userResponse = $response;
        $this->isCorrect = $this->correctResponse->matches($response);
        $this->answeredAt = $answeredAt;
    }
```

**Step 4: Run tests to verify they pass**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/ExamAttemptTest.php`
Expected: PASS.

**Step 5: Run PHPStan y Pint**

Run: `vendor/bin/phpstan analyse --no-progress --memory-limit=1G modules/Academic/Domain`
Expected: `[OK] No errors`.

Run: `vendor/bin/pint modules/Academic/Domain`
Expected: arregla formato.

**Step 6: Commit**

```bash
git add modules/Academic/Domain/Aggregates/ExamAttempt.php modules/Academic/Domain/Entities/AttemptQuestion.php modules/Academic/Tests/Unit/Domain/Aggregates/ExamAttemptTest.php
git commit -m "feat(academic): add exam attempt aggregate with snapshot and scoring"
```

---

### Task 6: Contrato `ExamAttemptRepository`

**Files:**
- Create: `modules/Academic/Domain/Repositories/ExamAttemptRepository.php`

**Step 1: Write the interface** (copia el estilo de `ExamRepository.php`)

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Repositories;

use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;

interface ExamAttemptRepository
{
    public function save(ExamAttempt $attempt): void;

    public function findById(ExamAttemptId $id): ?ExamAttempt;

    public function findActiveFor(ExamId $examId, string $userId): ?ExamAttempt;

    public function countCompletedFor(ExamId $examId, string $userId): int;

    /**
     * @return list<ExamAttempt>
     */
    public function all(?ExamId $examId = null, ?string $userId = null, ?ExamAttemptStatus $status = null): array;
}
```

**Step 2: Run existing suites to confirm nothing broke**

Run: `php artisan test modules/Foundation/Tests/Unit/DomainExceptionTest.php`
Expected: PASS.

**Step 3: Commit**

```bash
git add modules/Academic/Domain/Repositories/ExamAttemptRepository.php
git commit -m "feat(academic): add exam attempt repository contract"
```

---

### Task 7: Excepciones de aplicación del intento

**Files:**
- Create: `modules/Academic/Application/Exceptions/ExamAttemptNotFound.php`
- Create: `modules/Academic/Application/Exceptions/ExamAttemptLimitReached.php`
- Create: `modules/Academic/Application/Exceptions/ExamAttemptAlreadySubmitted.php`

**Step 1: Write the exceptions** (copia el patrón de `ExamNotFound.php`)

`ExamAttemptNotFound.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ExamAttemptNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: sprintf('No existe un intento de evaluación con el identificador %s.', $id),
            errorCode: 'EXAM_ATTEMPT_NOT_FOUND',
            statusCode: 404,
        );
    }
}
```

`ExamAttemptLimitReached.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ExamAttemptLimitReached extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Ya existe un intento activo o se alcanzó el máximo de intentos para este examen.',
            errorCode: 'EXAM_ATTEMPT_LIMIT_REACHED',
            statusCode: 409,
        );
    }
}
```

`ExamAttemptAlreadySubmitted.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ExamAttemptAlreadySubmitted extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Este intento de evaluación ya fue finalizado.',
            errorCode: 'EXAM_ATTEMPT_ALREADY_SUBMITTED',
            statusCode: 409,
        );
    }
}
```

**Step 2: Run a quick test**

Run: `php artisan test modules/Foundation/Tests/Unit/DomainExceptionTest.php`
Expected: PASS.

**Step 3: Commit**

```bash
git add modules/Academic/Application/Exceptions/ExamAttemptNotFound.php modules/Academic/Application/Exceptions/ExamAttemptLimitReached.php modules/Academic/Application/Exceptions/ExamAttemptAlreadySubmitted.php
git commit -m "feat(academic): add exam attempt application exceptions"
```

---

### Task 8: Comandos y consultas del intento

**Files:**
- Create: `modules/Academic/Application/Commands/StartExamAttemptCommand.php`
- Create: `modules/Academic/Application/Commands/AnswerAttemptQuestionCommand.php`
- Create: `modules/Academic/Application/Commands/SubmitExamAttemptCommand.php`
- Create: `modules/Academic/Application/Commands/CancelExamAttemptCommand.php`
- Create: `modules/Academic/Application/Queries/GetExamAttemptQuery.php`
- Create: `modules/Academic/Application/Queries/ListExamAttemptsQuery.php`

**Step 1: Write the commands/queries** (patrón `CreateExamCommand.php`)

`StartExamAttemptCommand.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class StartExamAttemptCommand implements Command
{
    public function __construct(
        public string $examId,
        public string $userId,
    ) {}
}
```

`AnswerAttemptQuestionCommand.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Academic\Domain\Entities\Responses\QuestionResponse;
use Modules\Foundation\Application\Commands\Command;

final readonly class AnswerAttemptQuestionCommand implements Command
{
    public function __construct(
        public string $attemptId,
        public string $userId,
        public int $position,
        public QuestionResponse $response,
    ) {}
}
```

`SubmitExamAttemptCommand.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class SubmitExamAttemptCommand implements Command
{
    public function __construct(
        public string $attemptId,
        public string $userId,
    ) {}
}
```

`CancelExamAttemptCommand.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CancelExamAttemptCommand implements Command
{
    public function __construct(
        public string $attemptId,
        public string $userId,
    ) {}
}
```

`GetExamAttemptQuery.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Queries;

use Modules\Foundation\Application\Commands\Command;

final readonly class GetExamAttemptQuery implements Command
{
    public function __construct(
        public string $attemptId,
        public string $userId,
        public bool $canViewOthers,
    ) {}
}
```

`ListExamAttemptsQuery.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Queries;

use Modules\Foundation\Application\Commands\Command;

final readonly class ListExamAttemptsQuery implements Command
{
    public function __construct(
        public ?string $examId = null,
        public ?string $userId = null,
        public ?string $status = null,
    ) {}
}
```

**Step 2: Run a quick test**

Run: `php artisan test modules/Foundation/Tests/Unit/DomainExceptionTest.php`
Expected: PASS.

**Step 3: Commit**

```bash
git add modules/Academic/Application/Commands/StartExamAttemptCommand.php modules/Academic/Application/Commands/AnswerAttemptQuestionCommand.php modules/Academic/Application/Commands/SubmitExamAttemptCommand.php modules/Academic/Application/Commands/CancelExamAttemptCommand.php modules/Academic/Application/Queries/GetExamAttemptQuery.php modules/Academic/Application/Queries/ListExamAttemptsQuery.php
git commit -m "feat(academic): add exam attempt commands and queries"
```

---

### Task 9: Respuestas y handlers del intento (TDD)

**Files:**
- Create: `modules/Academic/Application/Responses/ExamAttemptListItemResponse.php`
- Create: `modules/Academic/Application/Responses/ExamAttemptResponse.php`
- Create: `modules/Academic/Application/UseCases/StartExamAttemptHandler.php`
- Create: `modules/Academic/Application/UseCases/AnswerAttemptQuestionHandler.php`
- Create: `modules/Academic/Application/UseCases/SubmitExamAttemptHandler.php`
- Create: `modules/Academic/Application/UseCases/CancelExamAttemptHandler.php`
- Create: `modules/Academic/Application/UseCases/GetExamAttemptHandler.php`
- Create: `modules/Academic/Application/UseCases/ListExamAttemptsHandler.php`
- Test: `modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php`

**Step 1: Write the failing tests**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\AnswerAttemptQuestionCommand;
use Modules\Academic\Application\Commands\StartExamAttemptCommand;
use Modules\Academic\Application\Commands\SubmitExamAttemptCommand;
use Modules\Academic\Application\Exceptions\ExamAttemptAlreadySubmitted;
use Modules\Academic\Application\Exceptions\ExamAttemptLimitReached;
use Modules\Academic\Application\Exceptions\ExamAttemptNotFound;
use Modules\Academic\Application\Exceptions\ExamNotFound;
use Modules\Academic\Application\Queries\GetExamAttemptQuery;
use Modules\Academic\Application\Queries\ListExamAttemptsQuery;
use Modules\Academic\Application\Responses\ExamAttemptListItemResponse;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Application\UseCases\AnswerAttemptQuestionHandler;
use Modules\Academic\Application\UseCases\GetExamAttemptHandler;
use Modules\Academic\Application\UseCases\ListExamAttemptsHandler;
use Modules\Academic\Application\UseCases\StartExamAttemptHandler;
use Modules\Academic\Application\UseCases\SubmitExamAttemptHandler;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\QuestionResponse;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;

final class InMemoryExamAttemptRepository implements ExamAttemptRepository
{
    /** @var array<string, ExamAttempt> */
    public array $attempts = [];

    public function save(ExamAttempt $attempt): void
    {
        $this->attempts[$attempt->id()->value()] = $attempt;
    }

    public function findById(ExamAttemptId $id): ?ExamAttempt
    {
        return $this->attempts[$id->value()] ?? null;
    }

    public function findActiveFor(ExamId $examId, string $userId): ?ExamAttempt
    {
        foreach ($this->attempts as $attempt) {
            if ($attempt->examId()->equals($examId)
                && $attempt->userId() === $userId
                && $attempt->status() === ExamAttemptStatus::InProgress
            ) {
                return $attempt;
            }
        }

        return null;
    }

    public function countCompletedFor(ExamId $examId, string $userId): int
    {
        $count = 0;
        foreach ($this->attempts as $attempt) {
            if ($attempt->examId()->equals($examId)
                && $attempt->userId() === $userId
                && $attempt->status() !== ExamAttemptStatus::InProgress
            ) {
                $count++;
            }
        }

        return $count;
    }

    public function all(?ExamId $examId = null, ?string $userId = null, ?ExamAttemptStatus $status = null): array
    {
        return array_values(array_filter(
            $this->attempts,
            static fn (ExamAttempt $attempt): bool => ($examId === null || $attempt->examId()->equals($examId))
                && ($userId === null || $attempt->userId() === $userId)
                && ($status === null || $attempt->status() === $status),
        ));
    }
}

/** Persists a course with two questions and an exam, returning [examId, questionIds]. */
function persistedAttemptExam(): array
{
    $courseId = createDraftCourseForPublishing('EXM-'.strtoupper((string) Str::random(4)))->id()->value();
    $questionRepository = app(QuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());
    $questionIds = [];
    foreach (['opt-a', 'opt-b'] as $refId) {
        $question = Question::create(
            QuestionId::fromString((string) Str::uuid()),
            QuestionType::SingleChoice,
            $competencyId,
            '¿Pregunta '.$refId.'?',
            1,
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => $refId]),
            [
                QuestionOption::create($refId, QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
                QuestionOption::create('opt-x', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
            ],
        );
        $questionRepository->save($question);
        $questionIds[] = $question->id()->value();
    }

    $examRepository = app(ExamRepository::class);
    $exam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString($courseId),
        'Examen del intento',
        array_map(
            static fn (string $id, int $index): ExamQuestion => ExamQuestion::create($index + 1, QuestionId::fromString($id), 10),
            $questionIds,
            array_keys($questionIds),
        ),
        maxAttempts: 2,
        passingScore: 70,
    );
    $examRepository->save($exam);

    return [$exam->id()->value(), $questionIds];
}

function attemptStartHandler(): StartExamAttemptHandler
{
    return new StartExamAttemptHandler(
        new InMemoryExamAttemptRepository,
        app(ExamRepository::class),
        app(QuestionRepository::class),
    );
}

it('inicia un intento con snapshot del examen', function (): void {
    [$examId, $questionIds] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $handler = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));

    $response = $handler->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));

    expect($response)->toBeInstanceOf(ExamAttemptResponse::class)
        ->and($response->status)->toBe('in_progress')
        ->and($response->questions)->toHaveCount(2)
        ->and($repository->attempts)->toHaveCount(1);
});

it('rechaza iniciar un intento sobre un examen inexistente', function (): void {
    $repository = new InMemoryExamAttemptRepository;
    $handler = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));

    expect(fn () => $handler->handle(new StartExamAttemptCommand(examId: (string) Str::uuid(), userId: 'user-1')))
        ->toThrow(ExamNotFound::class);
});

it('rechaza un segundo intento activo para el mismo examen y usuario', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $handler = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $handler->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));

    expect(fn () => $handler->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1')))
        ->toThrow(ExamAttemptLimitReached::class);
});

it('rechaza iniciar un intento cuando se excede max_attempts', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $handler = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $submit = new SubmitExamAttemptHandler($repository);

    // max_attempts = 2
    $first = $handler->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));
    $submit->handle(new SubmitExamAttemptCommand($first->id, 'user-1'));
    $second = $handler->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));
    $submit->handle(new SubmitExamAttemptCommand($second->id, 'user-1'));

    expect(fn () => $handler->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1')))
        ->toThrow(ExamAttemptLimitReached::class);
});

it('responde una pregunta del intento', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $created = $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));

    $answer = new AnswerAttemptQuestionHandler($repository);
    $response = $answer->handle(new AnswerAttemptQuestionCommand(
        attemptId: $created->id,
        userId: 'user-1',
        position: 1,
        response: SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    ));

    expect($response->questions[0]['user_response'])->not->toBeNull();
});

it('rechaza responder un intento de otro usuario', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $created = $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));

    $answer = new AnswerAttemptQuestionHandler($repository);
    expect(fn () => $answer->handle(new AnswerAttemptQuestionCommand(
        attemptId: $created->id,
        userId: 'user-2',
        position: 1,
        response: SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    )))->toThrow(ExamAttemptNotFound::class);
});

it('envía el intento y calcula el resultado', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $created = $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));
    $answer = new AnswerAttemptQuestionHandler($repository);
    $answer->handle(new AnswerAttemptQuestionCommand($created->id, 'user-1', 1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a'])));
    $answer->handle(new AnswerAttemptQuestionCommand($created->id, 'user-1', 2, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-b'])));

    $response = (new SubmitExamAttemptHandler($repository))->handle(new SubmitExamAttemptCommand($created->id, 'user-1'));

    expect($response->status)->toBe('submitted')
        ->and($response->score)->toBe(20)
        ->and($response->percentage)->toBe(100)
        ->and($response->passed)->toBeTrue();
});

it('rechaza reenviar un intento ya enviado', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $created = $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));
    $submit = new SubmitExamAttemptHandler($repository);
    $submit->handle(new SubmitExamAttemptCommand($created->id, 'user-1'));

    expect(fn () => $submit->handle(new SubmitExamAttemptCommand($created->id, 'user-1')))
        ->toThrow(ExamAttemptAlreadySubmitted::class);
});

it('obtiene el detalle de un intento para su dueño y oculta a otros usuarios', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $created = $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));

    $detail = (new GetExamAttemptHandler($repository))->handle(new GetExamAttemptQuery($created->id, 'user-1', false));
    expect($detail)->toBeInstanceOf(ExamAttemptResponse::class);

    expect(fn () => (new GetExamAttemptHandler($repository))->handle(new GetExamAttemptQuery($created->id, 'user-2', false)))
        ->toThrow(ExamAttemptNotFound::class);
});

it('lista intentos filtrados', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));
    $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-2'));

    $all = (new ListExamAttemptsHandler($repository))->handle(new ListExamAttemptsQuery);
    expect($all)->toHaveCount(2)
        ->and($all[0])->toBeInstanceOf(ExamAttemptListItemResponse::class);

    $filtered = (new ListExamAttemptsHandler($repository))->handle(new ListExamAttemptsQuery(userId: 'user-1'));
    expect($filtered)->toHaveCount(1);
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php`
Expected: FAIL (classes not found).

**Step 3: Write responses**

`ExamAttemptListItemResponse.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\ExamAttempt;

final readonly class ExamAttemptListItemResponse
{
    public function __construct(
        public string $id,
        public string $examId,
        public string $userId,
        public string $status,
        public string $startedAt,
        public ?string $submittedAt,
        public int $score,
        public int $percentage,
        public bool $passed,
    ) {}

    public static function fromAttempt(ExamAttempt $attempt): self
    {
        return new self(
            $attempt->id()->value(),
            $attempt->examId()->value(),
            $attempt->userId(),
            $attempt->status()->value,
            $attempt->startedAt()->format(DATE_ATOM),
            $attempt->submittedAt()?->format(DATE_ATOM),
            $attempt->score(),
            $attempt->percentage(),
            $attempt->passed(),
        );
    }

    /** @return array{id: string, exam_id: string, user_id: string, status: string, started_at: string, submitted_at: string|null, score: int, percentage: int, passed: bool} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->examId,
            'user_id' => $this->userId,
            'status' => $this->status,
            'started_at' => $this->startedAt,
            'submitted_at' => $this->submittedAt,
            'score' => $this->score,
            'percentage' => $this->percentage,
            'passed' => $this->passed,
        ];
    }
}
```

`ExamAttemptResponse.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\Responses\QuestionResponse;

final readonly class ExamAttemptResponse
{
    /**
     * @param  list<array<string, mixed>>  $questions
     */
    private function __construct(
        public string $id,
        public string $examId,
        public string $userId,
        public string $status,
        public string $startedAt,
        public ?string $submittedAt,
        public string $title,
        public ?int $durationMinutes,
        public int $passingScore,
        public bool $shuffleQuestions,
        public string $feedbackMode,
        public array $questions,
        public int $score,
        public int $totalPoints,
        public int $percentage,
        public bool $passed,
    ) {}

    /**
     * @param  callable(AttemptQuestion): array<string, mixed>  $questionMapper
     */
    public static function fromAttempt(ExamAttempt $attempt, callable $questionMapper): self
    {
        return new self(
            $attempt->id()->value(),
            $attempt->examId()->value(),
            $attempt->userId(),
            $attempt->status()->value,
            $attempt->startedAt()->format(DATE_ATOM),
            $attempt->submittedAt()?->format(DATE_ATOM),
            $attempt->title(),
            $attempt->durationMinutes(),
            $attempt->passingScore(),
            $attempt->shuffleQuestions(),
            $attempt->feedbackMode()->value,
            array_map($questionMapper, $attempt->questions()),
            $attempt->score(),
            $attempt->totalPoints(),
            $attempt->percentage(),
            $attempt->passed(),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     exam_id: string,
     *     user_id: string,
     *     status: string,
     *     started_at: string,
     *     submitted_at: string|null,
     *     title: string,
     *     duration_minutes: int|null,
     *     passing_score: int,
     *     shuffle_questions: bool,
     *     feedback_mode: string,
     *     questions: list<array<string, mixed>>,
     *     score: int,
     *     total_points: int,
     *     percentage: int,
     *     passed: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->examId,
            'user_id' => $this->userId,
            'status' => $this->status,
            'started_at' => $this->startedAt,
            'submitted_at' => $this->submittedAt,
            'title' => $this->title,
            'duration_minutes' => $this->durationMinutes,
            'passing_score' => $this->passingScore,
            'shuffle_questions' => $this->shuffleQuestions,
            'feedback_mode' => $this->feedbackMode,
            'questions' => $this->questions,
            'score' => $this->score,
            'total_points' => $this->totalPoints,
            'percentage' => $this->percentage,
            'passed' => $this->passed,
        ];
    }
}
```

**Step 4: Write handlers**

`StartExamAttemptHandler.php` (requiere añadir método `attemptQuestionPayloads` privado que construye el snapshot; usa `QuestionResponseFactory` para la correct_response y serializa options):

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\StartExamAttemptCommand;
use Modules\Academic\Application\Exceptions\ExamAttemptLimitReached;
use Modules\Academic\Application\Exceptions\ExamNotFound;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;

final readonly class StartExamAttemptHandler
{
    public function __construct(
        private ExamAttemptRepository $attempts,
        private ExamRepository $exams,
        private QuestionRepository $questions,
    ) {}

    public function handle(StartExamAttemptCommand $command): ExamAttemptResponse
    {
        $examId = ExamId::fromString($command->examId);
        $exam = $this->exams->findById($examId);
        if ($exam === null) {
            throw ExamNotFound::withId($command->examId);
        }

        if ($this->attempts->findActiveFor($examId, $command->userId) !== null
            || $this->attempts->countCompletedFor($examId, $command->userId) >= $exam->maxAttempts()
        ) {
            throw ExamAttemptLimitReached::create();
        }

        $questions = $this->buildSnapshot($exam);

        $attempt = ExamAttempt::start(
            ExamAttemptId::fromString((string) Str::uuid()),
            $examId,
            $command->userId,
            $exam->title(),
            $exam->durationMinutes(),
            $exam->passingScore(),
            $exam->shuffleQuestions(),
            $exam->feedbackMode(),
            $questions,
            new DateTimeImmutable('now'),
        );
        $this->attempts->save($attempt);

        return ExamAttemptResponse::fromAttempt($attempt, $this->questionMapper(false));
    }

    /** @return list<AttemptQuestion> */
    private function buildSnapshot(\Modules\Academic\Domain\Aggregates\Exam $exam): array
    {
        $questions = [];
        foreach ($exam->questions() as $examQuestion) {
            $question = $this->questions->findById($examQuestion->questionId());
            if ($question === null) {
                continue;
            }
            $questions[] = AttemptQuestion::create(
                AttemptQuestionId::fromString((string) Str::uuid()),
                $examQuestion->position(),
                $examQuestion->questionId(),
                $examQuestion->points(),
                $question->prompt(),
                $question->type(),
                array_map(static fn ($option): array => [
                    'refId' => $option->refId(),
                    'id' => $option->id()->value(),
                    'label' => $option->label(),
                    'position' => $option->position(),
                    'side' => $option->side(),
                ], $question->options()),
                $question->response(),
                $question->explanation(),
            );
        }

        return $questions;
    }

    /** @return callable(AttemptQuestion): array<string, mixed> */
    private function questionMapper(bool $showFeedback): callable
    {
        return static function (AttemptQuestion $question) use ($showFeedback): array {
            $base = [
                'position' => $question->position(),
                'question_id' => $question->questionId()->value(),
                'type' => $question->type()->value,
                'points' => $question->points(),
                'prompt' => $question->prompt(),
                'options' => $question->options(),
                'user_response' => $question->userResponse()?->toArray(),
            ];
            if ($showFeedback) {
                $base['is_correct'] = $question->isCorrect();
                $base['correct_response'] = $question->correctResponse()->toArray();
                $base['explanation'] = $question->explanation();
            }

            return $base;
        };
    }
}
```

`AnswerAttemptQuestionHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Modules\Academic\Application\Commands\AnswerAttemptQuestionCommand;
use Modules\Academic\Application\Exceptions\ExamAttemptNotFound;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;

final readonly class AnswerAttemptQuestionHandler
{
    public function __construct(
        private ExamAttemptRepository $attempts,
    ) {}

    public function handle(AnswerAttemptQuestionCommand $command): ExamAttemptResponse
    {
        $attempt = $this->ownedAttempt($command->attemptId, $command->userId);

        $attempt->answer(
            $command->position,
            $command->response,
            new DateTimeImmutable('now'),
        );
        $this->attempts->save($attempt);

        return ExamAttemptResponse::fromAttempt($attempt, $this->questionMapper(false));
    }

    private function ownedAttempt(string $attemptId, string $userId): ExamAttempt
    {
        $attempt = $this->attempts->findById(ExamAttemptId::fromString($attemptId));
        if ($attempt === null || $attempt->userId() !== $userId) {
            throw ExamAttemptNotFound::withId($attemptId);
        }

        return $attempt;
    }

    /** @return callable(\Modules\Academic\Domain\Entities\AttemptQuestion): array<string, mixed> */
    private function questionMapper(bool $showFeedback): callable
    {
        return static function (\Modules\Academic\Domain\Entities\AttemptQuestion $question) use ($showFeedback): array {
            $base = [
                'position' => $question->position(),
                'question_id' => $question->questionId()->value(),
                'type' => $question->type()->value,
                'points' => $question->points(),
                'prompt' => $question->prompt(),
                'options' => $question->options(),
                'user_response' => $question->userResponse()?->toArray(),
            ];
            if ($showFeedback) {
                $base['is_correct'] = $question->isCorrect();
                $base['correct_response'] = $question->correctResponse()->toArray();
                $base['explanation'] = $question->explanation();
            }

            return $base;
        };
    }
}
```

`SubmitExamAttemptHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Modules\Academic\Application\Commands\SubmitExamAttemptCommand;
use Modules\Academic\Application\Exceptions\ExamAttemptAlreadySubmitted;
use Modules\Academic\Application\Exceptions\ExamAttemptNotFound;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;

final readonly class SubmitExamAttemptHandler
{
    public function __construct(
        private ExamAttemptRepository $attempts,
    ) {}

    public function handle(SubmitExamAttemptCommand $command): ExamAttemptResponse
    {
        $attempt = $this->ownedAttempt($command->attemptId, $command->userId);

        try {
            $attempt->submit(new DateTimeImmutable('now'));
        } catch (InvalidExamAttempt $exception) {
            throw ExamAttemptAlreadySubmitted::create();
        }
        $this->attempts->save($attempt);

        return ExamAttemptResponse::fromAttempt($attempt, $this->questionMapper(true));
    }

    private function ownedAttempt(string $attemptId, string $userId): ExamAttempt
    {
        $attempt = $this->attempts->findById(ExamAttemptId::fromString($attemptId));
        if ($attempt === null || $attempt->userId() !== $userId) {
            throw ExamAttemptNotFound::withId($attemptId);
        }

        return $attempt;
    }

    /** @return callable(\Modules\Academic\Domain\Entities\AttemptQuestion): array<string, mixed> */
    private function questionMapper(bool $showFeedback): callable
    {
        return static function (\Modules\Academic\Domain\Entities\AttemptQuestion $question) use ($showFeedback): array {
            $base = [
                'position' => $question->position(),
                'question_id' => $question->questionId()->value(),
                'type' => $question->type()->value,
                'points' => $question->points(),
                'prompt' => $question->prompt(),
                'options' => $question->options(),
                'user_response' => $question->userResponse()?->toArray(),
            ];
            if ($showFeedback) {
                $base['is_correct'] = $question->isCorrect();
                $base['correct_response'] = $question->correctResponse()->toArray();
                $base['explanation'] = $question->explanation();
            }

            return $base;
        };
    }
}
```

`CancelExamAttemptHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\CancelExamAttemptCommand;
use Modules\Academic\Application\Exceptions\ExamAttemptNotFound;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;

final readonly class CancelExamAttemptHandler
{
    public function __construct(
        private ExamAttemptRepository $attempts,
    ) {}

    public function handle(CancelExamAttemptCommand $command): ExamAttemptResponse
    {
        $attempt = $this->ownedAttempt($command->attemptId, $command->userId);

        $attempt->cancel();
        $this->attempts->save($attempt);

        return ExamAttemptResponse::fromAttempt($attempt, $this->questionMapper(true));
    }

    private function ownedAttempt(string $attemptId, string $userId): ExamAttempt
    {
        $attempt = $this->attempts->findById(ExamAttemptId::fromString($attemptId));
        if ($attempt === null || $attempt->userId() !== $userId) {
            throw ExamAttemptNotFound::withId($attemptId);
        }

        return $attempt;
    }

    /** @return callable(\Modules\Academic\Domain\Entities\AttemptQuestion): array<string, mixed> */
    private function questionMapper(bool $showFeedback): callable
    {
        return static function (\Modules\Academic\Domain\Entities\AttemptQuestion $question) use ($showFeedback): array {
            $base = [
                'position' => $question->position(),
                'question_id' => $question->questionId()->value(),
                'type' => $question->type()->value,
                'points' => $question->points(),
                'prompt' => $question->prompt(),
                'options' => $question->options(),
                'user_response' => $question->userResponse()?->toArray(),
            ];
            if ($showFeedback) {
                $base['is_correct'] = $question->isCorrect();
                $base['correct_response'] = $question->correctResponse()->toArray();
                $base['explanation'] = $question->explanation();
            }

            return $base;
        };
    }
}
```

`GetExamAttemptHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Exceptions\ExamAttemptNotFound;
use Modules\Academic\Application\Queries\GetExamAttemptQuery;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;

final readonly class GetExamAttemptHandler
{
    public function __construct(
        private ExamAttemptRepository $attempts,
    ) {}

    public function handle(GetExamAttemptQuery $query): ExamAttemptResponse
    {
        $attempt = $this->attempts->findById(ExamAttemptId::fromString($query->attemptId));
        if ($attempt === null) {
            throw ExamAttemptNotFound::withId($query->attemptId);
        }

        $isOwner = $attempt->userId() === $query->userId;
        if (! $isOwner && ! $query->canViewOthers) {
            throw ExamAttemptNotFound::withId($query->attemptId);
        }

        $showFeedback = $query->canViewOthers
            || $attempt->feedbackMode() !== ExamFeedbackMode::None;

        return ExamAttemptResponse::fromAttempt($attempt, $this->questionMapper($showFeedback));
    }

    /** @return callable(AttemptQuestion): array<string, mixed> */
    private function questionMapper(bool $showFeedback): callable
    {
        return static function (AttemptQuestion $question) use ($showFeedback): array {
            $base = [
                'position' => $question->position(),
                'question_id' => $question->questionId()->value(),
                'type' => $question->type()->value,
                'points' => $question->points(),
                'prompt' => $question->prompt(),
                'options' => $question->options(),
                'user_response' => $question->userResponse()?->toArray(),
            ];
            if ($showFeedback) {
                $base['is_correct'] = $question->isCorrect();
                $base['correct_response'] = $question->correctResponse()->toArray();
                $base['explanation'] = $question->explanation();
            }

            return $base;
        };
    }
}
```

`ListExamAttemptsHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\ListExamAttemptsQuery;
use Modules\Academic\Application\Responses\ExamAttemptListItemResponse;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\ValueObjects\ExamId;

final readonly class ListExamAttemptsHandler
{
    public function __construct(
        private ExamAttemptRepository $attempts,
    ) {}

    /** @return list<ExamAttemptListItemResponse> */
    public function handle(ListExamAttemptsQuery $query): array
    {
        return array_map(
            static fn ($attempt): ExamAttemptListItemResponse => ExamAttemptListItemResponse::fromAttempt($attempt),
            $this->attempts->all(
                $query->examId === null ? null : ExamId::fromString($query->examId),
                $query->userId,
                $query->status === null ? null : ExamAttemptStatus::from($query->status),
            ),
        );
    }
}
```

Nota de refactor (DRY): el `questionMapper(bool $showFeedback)` está duplicado en 5 handlers. Para simplificar el plan se acepta la duplicación inicial; si el revisor de calidad lo exige, extraer un servicio privado compartido `AttemptQuestionMapper` en `Application/Services`. En el commit de esta tarea se deja como está por ahora.

**Step 5: Run tests to verify they pass**

Run: `php artisan test modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php`
Expected: PASS.

**Step 6: Run PHPStan y Pint**

Run: `vendor/bin/phpstan analyse --no-progress --memory-limit=1G modules/Academic/Application`
Expected: `[OK] No errors`.

Run: `vendor/bin/pint modules/Academic/Application`
Expected: arregla formato.

**Step 7: Commit**

```bash
git add modules/Academic/Application/Responses/ExamAttemptListItemResponse.php modules/Academic/Application/Responses/ExamAttemptResponse.php modules/Academic/Application/UseCases/StartExamAttemptHandler.php modules/Academic/Application/UseCases/AnswerAttemptQuestionHandler.php modules/Academic/Application/UseCases/SubmitExamAttemptHandler.php modules/Academic/Application/UseCases/CancelExamAttemptHandler.php modules/Academic/Application/UseCases/GetExamAttemptHandler.php modules/Academic/Application/UseCases/ListExamAttemptsHandler.php modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php
git commit -m "feat(academic): add exam attempt use cases and responses"
```

---

### Task 10: Persistencia Eloquent del intento (TDD)

**Files:**
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/ExamAttemptModel.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/ExamAttemptQuestionModel.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentExamAttemptRepository.php`
- Test: `modules/Academic/Tests/Integration/EloquentExamAttemptRepositoryTest.php`

**Step 1: Write the failing tests**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentExamAttemptRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentExamRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

function attemptRepoFixtures(): array
{
    $courseId = createDraftCourseForPublishing('EXA-'.strtoupper((string) Str::random(4)))->id()->value();
    $questionRepository = app(QuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());
    $questionIds = [];
    foreach (['opt-a', 'opt-b'] as $refId) {
        $question = Question::create(
            QuestionId::fromString((string) Str::uuid()),
            QuestionType::SingleChoice,
            $competencyId,
            '¿Pregunta '.$refId.'?',
            1,
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => $refId]),
            [
                QuestionOption::create($refId, QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
                QuestionOption::create('opt-x', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
            ],
        );
        $questionRepository->save($question);
        $questionIds[] = $question->id()->value();
    }

    $exam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString($courseId),
        'Examen integración intento',
        array_map(
            static fn (string $id, int $index): ExamQuestion => ExamQuestion::create($index + 1, QuestionId::fromString($id), 10),
            $questionIds,
            array_keys($questionIds),
        ),
    );
    app(EloquentExamRepository::class)->save($exam);

    $userId = (string) Str::uuid();
    app(UserRepository::class)->save(User::register(
        id: $userId,
        name: 'Estudiante',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    ));

    return [$exam, $questionIds, $userId];
}

/** @return list<\Modules\Academic\Domain\Entities\AttemptQuestion> */
function attemptRepoQuestions(array $questionIds): array
{
    return array_map(
        static fn (string $id, int $index): \Modules\Academic\Domain\Entities\AttemptQuestion => \Modules\Academic\Domain\Entities\AttemptQuestion::create(
            AttemptQuestionId::fromString((string) Str::uuid()),
            $index + 1,
            QuestionId::fromString($id),
            10,
            '¿Pregunta '.$id.'?',
            QuestionType::SingleChoice,
            [],
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => $id]),
            'Explicación',
        ),
        $questionIds,
        array_keys($questionIds),
    );
}

it('guarda y reconstruye un intento con sus preguntas y respuestas', function (): void {
    [$exam, $questionIds, $userId] = attemptRepoFixtures();
    $repository = app(EloquentExamAttemptRepository::class);

    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString((string) Str::uuid()),
        $exam->id(),
        $userId,
        $exam->title(),
        45,
        70,
        false,
        ExamFeedbackMode::AfterSubmission,
        attemptRepoQuestions($questionIds),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );
    $attempt->answer(1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']), new DateTimeImmutable('2026-08-12 10:01:00'));
    $attempt->submit(new DateTimeImmutable('2026-08-12 10:10:00'));
    $repository->save($attempt);

    $stored = $repository->findById($attempt->id());

    expect($stored)->not->toBeNull()
        ->and($stored?->status())->toBe(ExamAttemptStatus::Submitted)
        ->and($stored?->score())->toBe(10)
        ->and($stored?->totalPoints())->toBe(20)
        ->and($stored?->percentage())->toBe(50)
        ->and($stored?->passed())->toBeFalse()
        ->and($stored?->questions())->toHaveCount(2)
        ->and($stored?->questions()[0]->userResponse())->not->toBeNull()
        ->and($stored?->questions()[0]->isCorrect())->toBeTrue();
});

it('encuentra el intento activo y cuenta los completados para max_attempts', function (): void {
    [$exam, $questionIds, $userId] = attemptRepoFixtures();
    $repository = app(EloquentExamAttemptRepository::class);

    $first = ExamAttempt::start(
        ExamAttemptId::fromString((string) Str::uuid()),
        $exam->id(),
        $userId,
        $exam->title(),
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptRepoQuestions($questionIds),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );
    $first->submit(new DateTimeImmutable('2026-08-12 10:10:00'));
    $repository->save($first);

    $second = ExamAttempt::start(
        ExamAttemptId::fromString((string) Str::uuid()),
        $exam->id(),
        $userId,
        $exam->title(),
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptRepoQuestions($questionIds),
        new DateTimeImmutable('2026-08-12 11:00:00'),
    );
    $repository->save($second);

    expect($repository->findActiveFor($exam->id(), $userId))->not->toBeNull()
        ->and($repository->countCompletedFor($exam->id(), $userId))->toBe(1);
});

it('lista intentos filtrados por usuario y estado', function (): void {
    [$exam, $questionIds, $userId] = attemptRepoFixtures();
    $repository = app(EloquentExamAttemptRepository::class);

    $repository->save(ExamAttempt::start(
        ExamAttemptId::fromString((string) Str::uuid()),
        $exam->id(),
        $userId,
        $exam->title(),
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptRepoQuestions($questionIds),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    ));

    $all = $repository->all();
    expect($all)->toHaveCount(1);

    $filtered = $repository->all(examId: $exam->id(), userId: $userId);
    expect($filtered)->toHaveCount(1);
});

it('borra el intento y sus preguntas en cascada', function (): void {
    [$exam, $questionIds, $userId] = attemptRepoFixtures();
    $repository = app(EloquentExamAttemptRepository::class);
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString((string) Str::uuid()),
        $exam->id(),
        $userId,
        $exam->title(),
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptRepoQuestions($questionIds),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );
    $repository->save($attempt);

    \Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ExamAttemptModel::query()
        ->where('id', $attempt->id()->value())
        ->delete();

    expect($repository->findById($attempt->id()))->toBeNull()
        ->and(\Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ExamAttemptQuestionModel::query()
            ->where('attempt_id', $attempt->id()->value())
            ->count())->toBe(0);
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test modules/Academic/Tests/Integration/EloquentExamAttemptRepositoryTest.php`
Expected: FAIL (models/repo not found).

**Step 3: Write models and repository**

`ExamAttemptModel.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ExamAttemptModel extends Model
{
    protected $table = 'academic_exam_attempts';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<ExamAttemptQuestionModel, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(ExamAttemptQuestionModel::class, 'attempt_id')->orderBy('position');
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'duration_minutes' => 'int',
            'passing_score' => 'int',
            'shuffle_questions' => 'bool',
            'feedback_mode' => 'string',
            'score' => 'int',
            'total_points' => 'int',
            'percentage' => 'int',
            'passed' => 'bool',
        ];
    }
}
```

`ExamAttemptQuestionModel.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class ExamAttemptQuestionModel extends Model
{
    protected $table = 'academic_exam_attempt_questions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'position' => 'int',
            'points' => 'int',
            'options' => 'array',
            'correct_response' => 'array',
            'user_response' => 'array',
            'is_correct' => 'bool',
            'answered_at' => 'datetime',
        ];
    }
}
```

`EloquentExamAttemptRepository.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Application\Services\QuestionResponseFactory;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ExamAttemptModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ExamAttemptQuestionModel;

final readonly class EloquentExamAttemptRepository implements ExamAttemptRepository
{
    public function save(ExamAttempt $attempt): void
    {
        DB::transaction(function () use ($attempt): void {
            $model = ExamAttemptModel::query()->updateOrCreate(
                ['id' => $attempt->id()->value()],
                [
                    'exam_id' => $attempt->examId()->value(),
                    'user_id' => $attempt->userId(),
                    'status' => $attempt->status()->value,
                    'started_at' => $attempt->startedAt(),
                    'submitted_at' => $attempt->submittedAt(),
                    'title' => $attempt->title(),
                    'duration_minutes' => $attempt->durationMinutes(),
                    'passing_score' => $attempt->passingScore(),
                    'shuffle_questions' => $attempt->shuffleQuestions(),
                    'feedback_mode' => $attempt->feedbackMode()->value,
                    'score' => $attempt->score(),
                    'total_points' => $attempt->totalPoints(),
                    'percentage' => $attempt->percentage(),
                    'passed' => $attempt->passed(),
                ],
            );

            $model->questions()->delete();

            foreach ($attempt->questions() as $question) {
                ExamAttemptQuestionModel::query()->create([
                    'id' => (string) Str::uuid(),
                    'attempt_id' => $model->id,
                    'position' => $question->position(),
                    'question_id' => $question->questionId()->value(),
                    'points' => $question->points(),
                    'prompt' => $question->prompt(),
                    'type' => $question->type()->value,
                    'options' => $question->options(),
                    'correct_response' => $question->correctResponse()->toArray(),
                    'explanation' => $question->explanation(),
                    'user_response' => $question->userResponse()?->toArray(),
                    'is_correct' => $question->isCorrect(),
                    'answered_at' => $question->answeredAt(),
                ]);
            }
        });
    }

    public function findById(ExamAttemptId $id): ?ExamAttempt
    {
        $model = ExamAttemptModel::query()->with('questions')->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findActiveFor(ExamId $examId, string $userId): ?ExamAttempt
    {
        $model = ExamAttemptModel::query()->with('questions')
            ->where('exam_id', $examId->value())
            ->where('user_id', $userId)
            ->where('status', ExamAttemptStatus::InProgress->value)
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function countCompletedFor(ExamId $examId, string $userId): int
    {
        return ExamAttemptModel::query()
            ->where('exam_id', $examId->value())
            ->where('user_id', $userId)
            ->where('status', '!=', ExamAttemptStatus::InProgress->value)
            ->count();
    }

    /** @return list<ExamAttempt> */
    public function all(?ExamId $examId = null, ?string $userId = null, ?ExamAttemptStatus $status = null): array
    {
        $builder = ExamAttemptModel::query()->with('questions');
        if ($examId !== null) {
            $builder->where('exam_id', $examId->value());
        }
        if ($userId !== null) {
            $builder->where('user_id', $userId);
        }
        if ($status !== null) {
            $builder->where('status', $status->value);
        }

        return array_values(
            $builder->orderBy('created_at')->get()
                ->map(fn (ExamAttemptModel $model): ExamAttempt => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(ExamAttemptModel $model): ExamAttempt
    {
        $questions = array_values($model->questions->map(fn (ExamAttemptQuestionModel $question): AttemptQuestion => $this->toAttemptQuestion($question))->all());

        return ExamAttempt::restore(
            ExamAttemptId::fromString((string) $model->getAttribute('id')),
            ExamId::fromString((string) $model->getAttribute('exam_id')),
            (string) $model->getAttribute('user_id'),
            ExamAttemptStatus::from((string) $model->getAttribute('status')),
            new DateTimeImmutable((string) $model->getAttribute('started_at')),
            $model->getAttribute('submitted_at') === null ? null : new DateTimeImmutable((string) $model->getAttribute('submitted_at')),
            (string) $model->getAttribute('title'),
            $model->getAttribute('duration_minutes') === null ? null : (int) $model->getAttribute('duration_minutes'),
            (int) $model->getAttribute('passing_score'),
            (bool) $model->getAttribute('shuffle_questions'),
            ExamFeedbackMode::from((string) $model->getAttribute('feedback_mode')),
            $questions,
            (int) $model->getAttribute('score'),
            (int) $model->getAttribute('total_points'),
            (int) $model->getAttribute('percentage'),
            (bool) $model->getAttribute('passed'),
        );
    }

    private function toAttemptQuestion(ExamAttemptQuestionModel $model): AttemptQuestion
    {
        /** @var array<string, mixed>|null $userResponse */
        $userResponse = $model->getAttribute('user_response');

        return AttemptQuestion::restore(
            AttemptQuestionId::fromString((string) $model->getAttribute('id')),
            (int) $model->getAttribute('position'),
            QuestionId::fromString((string) $model->getAttribute('question_id')),
            (int) $model->getAttribute('points'),
            (string) $model->getAttribute('prompt'),
            QuestionType::from((string) $model->getAttribute('type')),
            $model->getAttribute('options') ?? [],
            QuestionResponseFactory::fromPayload((string) $model->getAttribute('type'), $model->getAttribute('correct_response')),
            $model->getAttribute('explanation') === null ? null : (string) $model->getAttribute('explanation'),
            $userResponse === null ? null : QuestionResponseFactory::fromPayload((string) $model->getAttribute('type'), $userResponse),
            $model->getAttribute('is_correct') === null ? null : (bool) $model->getAttribute('is_correct'),
            $model->getAttribute('answered_at') === null ? null : new DateTimeImmutable((string) $model->getAttribute('answered_at')),
        );
    }
}
```

**Step 4: Run tests to verify they pass**

Run: `php artisan test modules/Academic/Tests/Integration/EloquentExamAttemptRepositoryTest.php`
Expected: PASS.

Nota: la unicidad del activo se garantiza por el índice parcial único; si el test de "encuentra el intento activo" falla al guardar dos `in_progress` (no ocurre: el segundo test envía el primero), revisar que el índice no bloquea.

**Step 5: Run PHPStan y Pint**

Run: `vendor/bin/phpstan analyse --no-progress --memory-limit=1G modules/Academic/Infrastructure`
Expected: `[OK] No errors`.

Run: `vendor/bin/pint modules/Academic/Infrastructure`
Expected: arregla formato.

**Step 6: Commit**

```bash
git add modules/Academic/Infrastructure/Persistence/Eloquent/Models/ExamAttemptModel.php modules/Academic/Infrastructure/Persistence/Eloquent/Models/ExamAttemptQuestionModel.php modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentExamAttemptRepository.php modules/Academic/Tests/Integration/EloquentExamAttemptRepositoryTest.php
git commit -m "feat(academic): add eloquent exam attempt repository"
```

---

### Task 11: Registrar handlers y bind en el provider

**Files:**
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`

**Step 1: Añadir imports** (después de los imports de exámenes existentes)

```php
use Modules\Academic\Application\Commands\AnswerAttemptQuestionCommand;
use Modules\Academic\Application\Commands\CancelExamAttemptCommand;
use Modules\Academic\Application\Commands\StartExamAttemptCommand;
use Modules\Academic\Application\Commands\SubmitExamAttemptCommand;
use Modules\Academic\Application\Queries\GetExamAttemptQuery;
use Modules\Academic\Application\Queries\ListExamAttemptsQuery;
use Modules\Academic\Application\UseCases\AnswerAttemptQuestionHandler;
use Modules\Academic\Application\UseCases\CancelExamAttemptHandler;
use Modules\Academic\Application\UseCases\GetExamAttemptHandler;
use Modules\Academic\Application\UseCases\ListExamAttemptsHandler;
use Modules\Academic\Application\UseCases\StartExamAttemptHandler;
use Modules\Academic\Application\UseCases\SubmitExamAttemptHandler;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentExamAttemptRepository;
```

**Step 2: Añadir el bind** (junto a los binds existentes)

```php
        $this->app->bind(
            ExamAttemptRepository::class,
            EloquentExamAttemptRepository::class,
        );
```

**Step 3: Añadir los registros** (junto a los de exámenes)

```php
        $registry->register(StartExamAttemptCommand::class, StartExamAttemptHandler::class);
        $registry->register(AnswerAttemptQuestionCommand::class, AnswerAttemptQuestionHandler::class);
        $registry->register(SubmitExamAttemptCommand::class, SubmitExamAttemptHandler::class);
        $registry->register(CancelExamAttemptCommand::class, CancelExamAttemptHandler::class);
        $registry->register(GetExamAttemptQuery::class, GetExamAttemptHandler::class);
        $registry->register(ListExamAttemptsQuery::class, ListExamAttemptsHandler::class);
```

**Step 4: Verificar**

Run: `php artisan route:list --path=academic` (aún sin rutas de intento, pero no debe romper)
Expected: las rutas existentes aparecen.

Run: `php artisan test modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php
git commit -m "feat(academic): register exam attempt handlers in provider"
```

---

### Task 12: Permiso `exam_attempts.view`

**Files:**
- Modify: `modules/Authorization/Domain/Enums/Permission.php`
- Modify: `modules/Authorization/Domain/Services/RolePermissions.php`
- Modify: `modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`

**Step 1: Añadir tests** (antes de escribir implementación)

Añadir al final de `RolePermissionsTest.php`:

```php
it('otorga consulta de intentos de evaluación al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ViewExamAttempts))->toBeTrue();
});

it('otorga consulta de intentos de evaluación a administradores institucionales y docentes, pero no a estudiantes', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewExamAttempts))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewExamAttempts))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewExamAttempts))->toBeFalse();
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`
Expected: FAIL (case `ViewExamAttempts` no existe).

**Step 3: Implementar**

En `Permission.php` añadir (después de `ViewExams`):

```php
    case ViewExamAttempts = 'exam_attempts.view';
```

En `RolePermissions.php`:

- En `Role::SuperAdmin` añadir `Permission::ViewExamAttempts,` después de `Permission::ViewExams`.
- En `Role::InstitutionalAdmin, Role::Teacher, Role::Student` NO se añade (Student no lo tiene); hay que mover Teacher e InstitutionalAdmin a un grupo con permiso. Reestructurar:

```php
        return match ($role) {
            Role::SuperAdmin => [
                Permission::ManageOrganizations,
                Permission::ViewOrganizations,
                Permission::ManageRoleAssignments,
                Permission::ManageCourses,
                Permission::ViewCourses,
                Permission::ManageCompetencies,
                Permission::ViewCompetencies,
                Permission::ManagePrograms,
                Permission::ViewPrograms,
                Permission::ManageQuestions,
                Permission::ViewQuestions,
                Permission::ManageExams,
                Permission::ViewExams,
                Permission::ViewExamAttempts,
            ],
            Role::InstitutionalAdmin, Role::Teacher => [
                Permission::ViewOrganizations,
                Permission::ViewCourses,
                Permission::ViewCompetencies,
                Permission::ViewPrograms,
                Permission::ViewQuestions,
                Permission::ViewExams,
                Permission::ViewExamAttempts,
            ],
            Role::Student => [
                Permission::ViewOrganizations,
                Permission::ViewCourses,
                Permission::ViewCompetencies,
                Permission::ViewPrograms,
                Permission::ViewQuestions,
                Permission::ViewExams,
            ],
        };
```

**Step 4: Run tests to verify they pass**

Run: `php artisan test modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Authorization/Domain/Enums/Permission.php modules/Authorization/Domain/Services/RolePermissions.php modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php
git commit -m "feat(authorization): add exam attempts view permission"
```

---

### Task 13: Controller, requests y rutas HTTP (TDD)

**Files:**
- Create: `modules/Academic/Presentation/Http/Controllers/ExamAttemptController.php`
- Create: `modules/Academic/Presentation/Http/Requests/StartExamAttemptRequest.php`
- Create: `modules/Academic/Presentation/Http/Requests/AnswerAttemptQuestionRequest.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Test: `modules/Academic/Tests/Feature/ExamAttemptTest.php`

**Step 1: Write the failing tests**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

/** @return list<string> */
function persistedAttemptQuestionIds(): array
{
    $questionRepository = app(QuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());
    $ids = [];
    foreach (['opt-a', 'opt-b'] as $refId) {
        $question = Question::create(
            QuestionId::fromString((string) Str::uuid()),
            QuestionType::SingleChoice,
            $competencyId,
            '¿Pregunta '.$refId.'?',
            1,
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => $refId]),
            [
                QuestionOption::create($refId, QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
                QuestionOption::create('opt-x', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
            ],
        );
        $questionRepository->save($question);
        $ids[] = $question->id()->value();
    }

    return $ids;
}

/** Persists an exam (max_attempts 2, passing 70) and returns its id. */
function persistedAttemptExamId(): string
{
    $courseId = createDraftCourseForPublishing('EXF-'.strtoupper((string) Str::random(4)))->id()->value();
    $questionIds = persistedAttemptQuestionIds();
    $exam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString($courseId),
        'Examen feature intento',
        array_map(
            static fn (string $id, int $index): ExamQuestion => ExamQuestion::create($index + 1, QuestionId::fromString($id), 10),
            $questionIds,
            array_keys($questionIds),
        ),
        maxAttempts: 2,
        passingScore: 70,
    );
    app(ExamRepository::class)->save($exam);

    return $exam->id()->value();
}

it('inicia un intento y responde sus preguntas', function (): void {
    /** @var TestCase $this */
    $user = actingAsAuthenticatedUser();
    $examId = persistedAttemptExamId();

    $started = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $examId])
        ->assertCreated()
        ->assertJsonPath('data.status', 'in_progress')
        ->assertJsonCount(2, 'data.questions');

    $attemptId = $started->json('data.id');

    $this->putJson("/api/v1/academic/exam-attempts/{$attemptId}/questions/1", [
        'response' => ['type' => 'single_choice', 'optionId' => 'opt-a'],
    ])->assertOk()
        ->assertJsonPath('data.questions.0.user_response.optionId', 'opt-a');
});

it('rechaza iniciar un intento sobre un examen inexistente', function (): void {
    /** @var TestCase $this */
    actingAsAuthenticatedUser();

    $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => (string) Str::uuid()])
        ->assertNotFound()
        ->assertJsonPath('code', 'EXAM_NOT_FOUND');
});

it('rechaza un segundo intento activo para el mismo examen y usuario', function (): void {
    /** @var TestCase $this */
    $user = actingAsAuthenticatedUser();
    $examId = persistedAttemptExamId();

    $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $examId])->assertCreated();

    $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $examId])
        ->assertStatus(409)
        ->assertJsonPath('code', 'EXAM_ATTEMPT_LIMIT_REACHED');
});

it('envía un intento y devuelve el resultado', function (): void {
    /** @var TestCase $this */
    $user = actingAsAuthenticatedUser();
    $examId = persistedAttemptExamId();

    $started = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $examId])->assertCreated();
    $attemptId = $started->json('data.id');
    $this->putJson("/api/v1/academic/exam-attempts/{$attemptId}/questions/1", ['response' => ['type' => 'single_choice', 'optionId' => 'opt-a']])->assertOk();
    $this->putJson("/api/v1/academic/exam-attempts/{$attemptId}/questions/2", ['response' => ['type' => 'single_choice', 'optionId' => 'opt-b']])->assertOk();

    $this->postJson("/api/v1/academic/exam-attempts/{$attemptId}/submit")
        ->assertOk()
        ->assertJsonPath('data.status', 'submitted')
        ->assertJsonPath('data.score', 20)
        ->assertJsonPath('data.percentage', 100)
        ->assertJsonPath('data.passed', true);

    $this->postJson("/api/v1/academic/exam-attempts/{$attemptId}/submit")
        ->assertStatus(409)
        ->assertJsonPath('code', 'EXAM_ATTEMPT_ALREADY_SUBMITTED');
});

it('rechaza responder o enviar un intento de otro usuario', function (): void {
    /** @var TestCase $this */
    $owner = actingAsAuthenticatedUser();
    $examId = persistedAttemptExamId();
    $started = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $examId])->assertCreated();
    $attemptId = $started->json('data.id');

    actingAsAuthenticatedUser();

    $this->putJson("/api/v1/academic/exam-attempts/{$attemptId}/questions/1", ['response' => ['type' => 'single_choice', 'optionId' => 'opt-a']])
        ->assertNotFound()
        ->assertJsonPath('code', 'EXAM_ATTEMPT_NOT_FOUND');

    $this->postJson("/api/v1/academic/exam-attempts/{$attemptId}/submit")
        ->assertNotFound()
        ->assertJsonPath('code', 'EXAM_ATTEMPT_NOT_FOUND');
});

it('oculta la retroalimentación al estudiante con feedback_mode none', function (): void {
    /** @var TestCase $this */
    $user = actingAsAuthenticatedUser();
    $courseId = createDraftCourseForPublishing('EXN-'.strtoupper((string) Str::random(4)))->id()->value();
    $questionIds = persistedAttemptQuestionIds();
    $exam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString($courseId),
        'Examen sin feedback',
        array_map(
            static fn (string $id, int $index): ExamQuestion => ExamQuestion::create($index + 1, QuestionId::fromString($id), 10),
            $questionIds,
            array_keys($questionIds),
        ),
        passingScore: 70,
        feedbackMode: \Modules\Academic\Domain\Enums\ExamFeedbackMode::None,
    );
    app(ExamRepository::class)->save($exam);

    $started = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $exam->id()->value()])->assertCreated();
    $attemptId = $started->json('data.id');
    $this->putJson("/api/v1/academic/exam-attempts/{$attemptId}/questions/1", ['response' => ['type' => 'single_choice', 'optionId' => 'opt-a']])->assertOk();

    $this->getJson("/api/v1/academic/exam-attempts/{$attemptId}")
        ->assertOk()
        ->assertJsonMissing(['is_correct'])
        ->assertJsonMissing(['correct_response'])
        ->assertJsonMissing(['explanation']);
});

it('permite a un docente con permiso ver intentos de terceros', function (): void {
    /** @var TestCase $this */
    $owner = actingAsAuthenticatedUser();
    $examId = persistedAttemptExamId();
    $started = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $examId])->assertCreated();
    $attemptId = $started->json('data.id');

    actingAsRole(Role::Teacher);

    $this->getJson("/api/v1/academic/exam-attempts/{$attemptId}")
        ->assertOk()
        ->assertJsonPath('data.user_id', $owner->id);

    $this->getJson('/api/v1/academic/exam-attempts')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('niega a un estudiante listar intentos', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);

    $this->getJson('/api/v1/academic/exam-attempts')
        ->assertForbidden();
});

it('protege los endpoints de intentos con autenticación', function (): void {
    /** @var TestCase $this */
    $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => (string) Str::uuid()])->assertUnauthorized();
    $this->putJson('/api/v1/academic/exam-attempts/'.Str::uuid().'/questions/1', [])->assertUnauthorized();
    $this->postJson('/api/v1/academic/exam-attempts/'.Str::uuid().'/submit')->assertUnauthorized();
    $this->postJson('/api/v1/academic/exam-attempts/'.Str::uuid().'/cancel')->assertUnauthorized();
    $this->getJson('/api/v1/academic/exam-attempts/'.Str::uuid())->assertUnauthorized();
    $this->getJson('/api/v1/academic/exam-attempts')->assertUnauthorized();
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test modules/Academic/Tests/Feature/ExamAttemptTest.php`
Expected: FAIL (routes/controller not found → 404).

**Step 3: Write requests, controller y rutas**

`StartExamAttemptRequest.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StartExamAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'exam_id' => ['required', 'string', 'uuid'],
        ];
    }
}
```

`AnswerAttemptQuestionRequest.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AnswerAttemptQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'response' => ['required', 'array'],
        ];
    }
}
```

`ExamAttemptController.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Application\Commands\AnswerAttemptQuestionCommand;
use Modules\Academic\Application\Commands\CancelExamAttemptCommand;
use Modules\Academic\Application\Commands\StartExamAttemptCommand;
use Modules\Academic\Application\Commands\SubmitExamAttemptCommand;
use Modules\Academic\Application\Queries\GetExamAttemptQuery;
use Modules\Academic\Application\Queries\ListExamAttemptsQuery;
use Modules\Academic\Application\Responses\ExamAttemptListItemResponse;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Application\Services\QuestionResponseFactory;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Presentation\Http\Requests\AnswerAttemptQuestionRequest;
use Modules\Academic\Presentation\Http\Requests\StartExamAttemptRequest;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class ExamAttemptController
{
    public function start(StartExamAttemptRequest $request, CommandBus $commandBus): JsonResponse
    {
        $user = $request->user();
        $result = $commandBus->dispatch(new StartExamAttemptCommand(
            examId: (string) $request->validated('exam_id'),
            userId: (string) $user->getAuthIdentifier(),
        ));
        assert($result instanceof ExamAttemptResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function answer(string $attemptId, int $position, AnswerAttemptQuestionRequest $request, CommandBus $commandBus): JsonResponse
    {
        $user = $request->user();
        $question = $this->attemptQuestion($request, $attemptId, $position);
        $response = QuestionResponseFactory::fromPayload($question->type()->value, (array) $request->validated('response'));

        $result = $commandBus->dispatch(new AnswerAttemptQuestionCommand(
            attemptId: $attemptId,
            userId: (string) $user->getAuthIdentifier(),
            position: $position,
            response: $response,
        ));
        assert($result instanceof ExamAttemptResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function submit(string $attemptId, Request $request, CommandBus $commandBus): JsonResponse
    {
        $user = $request->user();
        $result = $commandBus->dispatch(new SubmitExamAttemptCommand(
            attemptId: $attemptId,
            userId: (string) $user->getAuthIdentifier(),
        ));
        assert($result instanceof ExamAttemptResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function cancel(string $attemptId, Request $request, CommandBus $commandBus): JsonResponse
    {
        $user = $request->user();
        $result = $commandBus->dispatch(new CancelExamAttemptCommand(
            attemptId: $attemptId,
            userId: (string) $user->getAuthIdentifier(),
        ));
        assert($result instanceof ExamAttemptResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function show(string $attemptId, Request $request, QueryBus $queryBus, PermissionChecker $permissionChecker): JsonResponse
    {
        $user = $request->user();
        $result = $queryBus->ask(new GetExamAttemptQuery(
            attemptId: $attemptId,
            userId: (string) $user->getAuthIdentifier(),
            canViewOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ViewExamAttempts),
        ));
        assert($result instanceof ExamAttemptResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function index(Request $request, QueryBus $queryBus): JsonResponse
    {
        $examId = $request->query('exam_id');
        $userId = $request->query('user_id');
        $status = $request->query('status');

        $result = $queryBus->ask(new ListExamAttemptsQuery(
            examId: is_string($examId) && $examId !== '' ? $examId : null,
            userId: is_string($userId) && $userId !== '' ? $userId : null,
            status: is_string($status) && $status !== '' ? $status : null,
        ));
        assert(is_array($result));

        /** @var list<ExamAttemptListItemResponse> $result */
        return response()->json(['data' => array_map(
            static fn (ExamAttemptListItemResponse $attempt): array => $attempt->toArray(),
            $result,
        )]);
    }

    private function attemptQuestion(Request $request, string $attemptId, int $position): Question
    {
        $user = $request->user();
        $attempt = app(\Modules\Academic\Domain\Repositories\ExamAttemptRepository::class)
            ->findById(\Modules\Academic\Domain\ValueObjects\ExamAttemptId::fromString($attemptId));
        if ($attempt === null || $attempt->userId() !== (string) $user->getAuthIdentifier()) {
            throw \Modules\Academic\Application\Exceptions\ExamAttemptNotFound::withId($attemptId);
        }

        $question = $attempt->questionAt($position);
        if ($question === null) {
            throw \Modules\Academic\Domain\Exceptions\InvalidExamAttempt::create();
        }

        $bankQuestion = app(QuestionRepository::class)->findById($question->questionId());
        if ($bankQuestion === null) {
            throw \Modules\Academic\Application\Exceptions\QuestionNotFound::withId($question->questionId()->value());
        }

        return $bankQuestion;
    }
}
```

En `Routes/api.php`, dentro del grupo `auth:sanctum`, añadir tras las rutas de exámenes:

```php
            Route::middleware('permission:exam_attempts.view')->group(function (): void {
                Route::get('/exam-attempts', [ExamAttemptController::class, 'index'])
                    ->name('exam-attempts.index');
            });

            Route::get('/exam-attempts/{attemptId}', [ExamAttemptController::class, 'show'])
                ->whereUuid('attemptId')
                ->name('exam-attempts.show');

            Route::post('/exam-attempts', [ExamAttemptController::class, 'start'])
                ->name('exam-attempts.store');

            Route::put('/exam-attempts/{attemptId}/questions/{position}', [ExamAttemptController::class, 'answer'])
                ->whereUuid('attemptId')
                ->whereNumber('position')
                ->name('exam-attempts.questions.update');

            Route::post('/exam-attempts/{attemptId}/submit', [ExamAttemptController::class, 'submit'])
                ->whereUuid('attemptId')
                ->name('exam-attempts.submit');

            Route::post('/exam-attempts/{attemptId}/cancel', [ExamAttemptController::class, 'cancel'])
                ->whereUuid('attemptId')
                ->name('exam-attempts.cancel');
```

Añadir import `use Modules\Academic\Presentation\Http\Controllers\ExamAttemptController;` al inicio de `api.php`.

**Step 4: Run tests to verify they pass**

Run: `php artisan test modules/Academic/Tests/Feature/ExamAttemptTest.php`
Expected: PASS.

**Step 5: Run full exam-related suites y PHPStan**

Run: `php artisan test modules/Academic/Tests/Feature/ExamAttemptTest.php modules/Academic/Tests/Feature/ExamTest.php modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php`
Expected: PASS.

Run: `vendor/bin/phpstan analyse --no-progress --memory-limit=1G modules/Academic/Presentation`
Expected: `[OK] No errors`.

Run: `vendor/bin/pint modules/Academic/Presentation modules/Academic/Infrastructure/Providers`
Expected: arregla formato.

**Step 6: Commit**

```bash
git add modules/Academic/Presentation/Http/Controllers/ExamAttemptController.php modules/Academic/Presentation/Http/Requests/StartExamAttemptRequest.php modules/Academic/Presentation/Http/Requests/AnswerAttemptQuestionRequest.php modules/Academic/Presentation/Routes/api.php modules/Academic/Tests/Feature/ExamAttemptTest.php
git commit -m "feat(academic): expose exam attempts http api"
```

---

### Task 14: Validación final

**Files:** ninguno (solo ejecutar).

**Step 1: Pint sobre todo el repo**

Run: `vendor/bin/pint`
Expected: todos los archivos en formato correcto.

**Step 2: PHPStan nivel 8**

Run: `vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
Expected: `[OK] No errors`.

**Step 3: Migración en PostgreSQL y SQLite**

Run: `php artisan migrate --force`
Expected: `2026_08_12_000001_create_academic_exam_attempt_tables` aplicada.

Run: `php artisan migrate:status`
Expected: la migración del intento en estado `Ran`.

**Step 4: Suite completa**

Run: `php artisan test`
Expected: todo PASS (root + todos los módulos).

**Step 5: Verificar rutas**

Run: `php artisan route:list --path=academic/exam-attempts`
Expected: 6 rutas bajo `api/v1/academic/exam-attempts`.

**Step 6: Commit (si hubo cambios de Pint/PHPStan)**

```bash
git add -A
git commit -m "chore: apply formatting fixes"
```

---

### Task 15: Actualizar roadmap, ENG-LOG y plan (cierre)

**Files:**
- Modify: `docs/roadmap/ENG-000-roadmap-tecnico-backend.md`
- Modify: `docs/engineering/ENG-LOG.md`

**Step 1: Actualizar roadmap** (sección 12, ENG-032): cambiar `Estado: Pendiente` → `Completado`, y añadir nota estilo ENG-031 resumiendo el incremento (agregado `ExamAttempt` con snapshot inmutable, estados, resultados básicos, permisos, errores públicos, referencias a plan y ENG-LOG).

**Step 2: Añadir entrada al ENG-LOG** (`## 2026-08-12 — IMP-032 (Cierre de ENG-032 — Intentos de evaluación)`), con el detalle de las 4-5 secciones: modelo de dominio, persistencia, aplicación, presentación/permisos, pruebas y validaciones (Pint/PHPStan/suites/migrate/route:list), estilo de IMP-031.

**Step 3: Commit**

```bash
git add docs/roadmap/ENG-000-roadmap-tecnico-backend.md docs/engineering/ENG-LOG.md
git commit -m "docs(engineering): update roadmap and log for ENG-032 completion"
```

---

## Notas de riesgos y decisiones

- **Snapshot inmutable:** el intento copia config + preguntas al iniciar; `user_response`/`is_correct`/`answered_at` se actualizan en la fila de `academic_exam_attempt_questions`; el `save` reemplaza las preguntas del intento (delete + create) en transacción.
- **Concurrencia en `start`:** índice parcial único `(exam_id, user_id) WHERE status = 'in_progress'` (SQL compatible Postgres y SQLite 3.8+). El handler valida primero (`findActiveFor`/`countCompletedFor`).
- **Feedback:** `is_correct`/`correct_response`/`explanation` siempre se persisten; la exposición depende de `feedback_mode` y del rol (Student vs `exam_attempts.view`).
- **`max_attempts`:** se cuenta sobre intentos finalizados (submitted/canceled), no sobre el activo.
- **Timeout:** al `submit()`, si `duration_minutes` definido y excede, el intento pasa a `canceled` (sin score).
- **`matches()` en respuestas tipadas de ENG-030:** toca 5 archivos existentes + interface; preservar tests.
- **Duplicación `questionMapper`:** presente en 5 handlers de intento; aceptada en el plan; si la revisión lo exige, extraer `Application/Services/AttemptQuestionMapper`.
- **Verificación cruzada:** tests del módulo Academic siempre verdes; Pint antes de cada commit si se tocó PHP.
