# ENG-030 — Banco de preguntas — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implementar el banco de preguntas (ENG-030) en el módulo Academic: agregado `Question` con respuesta tipada por tipo, persistencia normalizada (tabla única + opciones) con `response` JSONB, CQRS completo (create/update/delete/get/list), API HTTP protegida con permisos `questions.manage`/`questions.view`, todo con TDD.

**Architecture:** Hexagonal dentro de `Modules\Academic` (Domain → Application → Infrastructure → Presentation), CQRS vía `CommandBus`/`QueryBus` y `MessageHandlerRegistry`. Respuestas tipadas por tipo como ValueObjects en dominio (patrón `ContentBlock`), `response` JSONB como payload autoritativo (patrón `snapshot` de CourseVersion), opciones normalizadas en tabla hija. Las preguntas se anclan por competencia (FK a `academic_competencies`).

**Tech Stack:** PHP 8.4, Laravel, Eloquent, PostgreSQL + SQLite (tests), Pest, PHPStan nivel 8, Pint. Validación solo dentro de contenedor desechable con `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test <path>`.

**Design doc:** `docs/plans/2026-08-10-banco-preguntas-eng030-design.md` (commiteado en `a2df359`).

---
## Convenciones del proyecto (leer antes de empezar)

- Todos los comandos de test/Pint/PHPStan/migrate se ejecutan dentro del contenedor **desechable** (patrón arriba). El contenedor fijo `edudrive-app` monta una copia stale en `C:\Users\...`; NO usarlo.
- PHPStan lento: `vendor/bin/phpstan analyse --no-progress --memory-limit=1G` con timeout generoso.
- Pest aísla archivos de test: helpers compartidos van en `tests/Pest.php`, no en archivos concretos.
- Los dataset de Pest NO deben ser `static` (error "Cannot bind an instance to a static closure").
- Errores públicos: extender `Modules\Foundation\Domain\Exceptions\DomainException` (message, errorCode, statusCode). Ya hay render global en `bootstrap/app.php` que los convierte a `{message, status, code}` para rutas `api/*`.
- Errores de dominio de validación usan patrones como `InvalidContentBlock::create()` (422). Not-found de aplicación usa `CourseNotFound::withId($id)` (404). Ver `modules/Academic/Application/Exceptions/CourseNotFound.php`.
- Commit style: `feat(academic): ...`, `docs(engineering): ...`. Frecuente, tras cada tarea verde.
- ValueObjects de UUID siguen `CompetencyId`/`CourseId` (regex `/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/` + `strtolower(trim())`).
- URLs HTTPS externas validan con `ExternalContentUrl` (máx 2048, parse_url, scheme https, sin user/pass).

## Vista previa de archivos (mapa)

**Domain** (crear):
- `Domain/Enums/QuestionType.php`
- `Domain/ValueObjects/QuestionId.php`
- `Domain/ValueObjects/QuestionOptionId.php`
- `Domain/ValueObjects/QuestionMedia.php` (lista `[{type, url}]`)
- `Domain/Entities/QuestionOption.php`
- `Domain/Entities/Responses/QuestionResponse.php` (interfaz)
- `Domain/Entities/Responses/SingleChoiceResponse.php`
- `Domain/Entities/Responses/MultiSelectResponse.php`
- `Domain/Entities/Responses/TrueFalseResponse.php`
- `Domain/Entities/Responses/MatchingResponse.php`
- `Domain/Entities/Responses/OrderingResponse.php`
- `Domain/Exceptions/InvalidQuestion.php` (422 `INVALID_QUESTION`)
- `Domain/Exceptions/InvalidQuestionScore.php` (422 `INVALID_QUESTION_SCORE`)
- `Domain/Aggregates/Question.php`
- `Domain/Repositories/QuestionRepository.php`

**Application** (crear):
- `Commands/CreateQuestionCommand.php`
- `Commands/UpdateQuestionCommand.php`
- `Commands/DeleteQuestionCommand.php`
- `Queries/GetQuestionQuery.php`
- `Queries/ListQuestionsQuery.php`
- `UseCases/CreateQuestionHandler.php`
- `UseCases/UpdateQuestionHandler.php`
- `UseCases/DeleteQuestionHandler.php`
- `UseCases/GetQuestionHandler.php`
- `UseCases/ListQuestionsHandler.php`
- `Exceptions/QuestionNotFound.php` (404 `QUESTION_NOT_FOUND`)
- `Responses/QuestionResponse.php`
- `Responses/QuestionListItemResponse.php`
- `Services/QuestionResponseFactory.php` (parsea `response` JSONB ↔ ValueObject)

**Infrastructure** (crear):
- `Persistence/Migrations/2026_08_10_000002_create_academic_questions_tables.php`
- `Persistence/Eloquent/Models/QuestionModel.php`
- `Persistence/Eloquent/Models/QuestionOptionModel.php`
- `Persistence/Eloquent/Repositories/EloquentQuestionRepository.php`

**Infrastructure** (modificar):
- `Infrastructure/Providers/AcademicServiceProvider.php` (bind + registrar handlers)

**Presentation** (crear):
- `Http/Controllers/QuestionController.php`
- `Http/Requests/CreateQuestionRequest.php`
- `Http/Requests/UpdateQuestionRequest.php`

**Presentation** (modificar):
- `Presentation/Routes/api.php` (5 rutas)

**Authorization** (modificar):
- `modules/Authorization/Domain/Enums/Permission.php` (2 permisos)
- `modules/Authorization/Domain/Services/RolePermissions.php` (grants)

**Tests** (crear):
- `Tests/Unit/Domain/Aggregates/QuestionTest.php`
- `Tests/Unit/Domain/Entities/QuestionResponseTest.php`
- `Tests/Unit/Application/QuestionHandlerTest.php`
- `Tests/Integration/EloquentQuestionRepositoryTest.php`
- `Tests/Feature/QuestionTest.php`

**Tests/helpers** (modificar):
- `tests/Pest.php` (helper para crear competencia persistida para tests de aplicación/integración)

**Documentación** (modificar):
- `docs/roadmap/ENG-000-roadmap-tecnico-backend.md` (ENG-030 → Completado + changelog 1.10.0)
- `docs/engineering/ENG-LOG.md` (entrada IMP-030)

---

### Task 1: Migración de las tablas de preguntas

**Files:**
- Create: `modules/Academic/Infrastructure/Persistence/Migrations/2026_08_10_000002_create_academic_questions_tables.php`

**Step 1: Write the migration** (usar `rawColumn` para CHECK con bloques `DB::statement` solo para el CHECK de Postgres, imitando la migración de unit content):

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

        Schema::create('academic_questions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('competency_id')->constrained('academic_competencies')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('prompt', 1000);
            $table->string('explanation', 2000)->nullable();
            $table->integer('score');
            $table->json('media')->nullable();
            $table->json('response');
            $table->timestampsTz();
        });

        Schema::create('academic_question_options', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained('academic_questions')->cascadeOnDelete();
            $table->string('side', 10)->nullable();
            $table->string('label', 500);
            $table->integer('position');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_question_options');
        Schema::dropIfExists('academic_questions');
    }
};
```

**Step 2: Run the migration against test DB to verify it compiles on both drivers**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\\vr506\\EDUDRIVE\\edudrive-api:/var/www/html" edudrive-app php artisan migrate:fresh --seed --env=testing` — sin embargo esto borra la DB de tests; mejor calificar con los tests de integración (Task 11). Si se quiere verificar sólo sintaxis: `php artisan migrate:status`. Confirmar que la migración aparece `Pending`.

**Step 3: Commit**

```bash
git add modules/Academic/Infrastructure/Persistence/Migrations/
git commit -m "feat(academic): add question bank tables migration"
```

---

### Task 2: ValueObjects base del dominio

**Files:**
- Create: `modules/Academic/Domain/ValueObjects/QuestionId.php`
- Create: `modules/Academic/Domain/ValueObjects/QuestionOptionId.php`
- Create: `modules/Academic/Domain/ValueObjects/QuestionMedia.php`

**Step 1: Write QuestionId** (copia del patrón `CompetencyId`):

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class QuestionId
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $value) !== 1) {
            throw new InvalidArgumentException('El identificador de la pregunta debe ser un UUID válido.');
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

**Step 2: Write QuestionOptionId** — idéntico pero mensaje "El identificador de la opción debe ser un UUID válido." y clase `QuestionOptionId`.

**Step 3: Write QuestionMedia**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

use Modules\Academic\Domain\Exceptions\InvalidQuestion;

final readonly class QuestionMedia
{
    private const array ALLOWED_TYPES = ['image', 'video', 'audio'];

    private function __construct(
        public string $type,
        public string $url,
    ) {}

    /** @param array{type: mixed, url: mixed} $data */
    public static function fromArray(array $data): self
    {
        $type = is_string($data['type'] ?? null) ? trim($data['type']) : '';
        $url = is_string($data['url'] ?? null) ? trim($data['url']) : '';

        if (! in_array($type, self::ALLOWED_TYPES, true)) {
            throw InvalidQuestion::create();
        }

        ExternalContentUrl::fromString($url);

        return new self($type, $url);
    }

    /** @return array{type: string, url: string} */
    public function toArray(): array
    {
        return ['type' => $this->type, 'url' => $this->url];
    }
}
```

**Step 4: Commit**

```bash
git add modules/Academic/Domain/ValueObjects/QuestionId.php modules/Academic/Domain/ValueObjects/QuestionOptionId.php modules/Academic/Domain/ValueObjects/QuestionMedia.php
git commit -m "feat(academic): add question value objects"
```

---

### Task 3: Enum `QuestionType` y excepciones públicas

**Files:**
- Create: `modules/Academic/Domain/Enums/QuestionType.php`
- Create: `modules/Academic/Domain/Exceptions/InvalidQuestion.php`
- Create: `modules/Academic/Domain/Exceptions/InvalidQuestionScore.php`

**Step 1: Write QuestionType**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum QuestionType: string
{
    case SingleChoice = 'single_choice';
    case MultiSelect = 'multi_select';
    case TrueFalse = 'true_false';
    case Matching = 'matching';
    case Ordering = 'ordering';
    case Situational = 'situational';
}
```

**Step 2: Write InvalidQuestion** (patrón `InvalidContentBlock::create()`)

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidQuestion extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La pregunta no es válida.',
            errorCode: 'INVALID_QUESTION',
            statusCode: 422,
        );
    }
}
```

**Step 3: Write InvalidQuestionScore**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidQuestionScore extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El puntaje de la pregunta debe ser un entero positivo.',
            errorCode: 'INVALID_QUESTION_SCORE',
            statusCode: 422,
        );
    }
}
```

**Step 4: Commit**

```bash
git add modules/Academic/Domain/Enums/QuestionType.php modules/Academic/Domain/Exceptions/InvalidQuestion.php modules/Academic/Domain/Exceptions/InvalidQuestionScore.php
git commit -m "feat(academic): add question type enum and exceptions"
```

---

### Task 4: Respuestas tipadas por tipo (TDD)

**Files:**
- Create: `modules/Academic/Domain/Entities/Responses/QuestionResponse.php`
- Create: `modules/Academic/Domain/Entities/Responses/SingleChoiceResponse.php`
- Create: `modules/Academic/Domain/Entities/Responses/MultiSelectResponse.php`
- Create: `modules/Academic/Domain/Entities/Responses/TrueFalseResponse.php`
- Create: `modules/Academic/Domain/Entities/Responses/MatchingResponse.php`
- Create: `modules/Academic/Domain/Entities/Responses/OrderingResponse.php`
- Test: `modules/Academic/Tests/Unit/Domain/Entities/QuestionResponseTest.php`

**Step 1: Write interface**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities\Responses;

/** Forma canónica serializable de la respuesta correcta de una pregunta. */
interface QuestionResponse
{
    /** @return array<string, mixed> */
    public function toArray(): array;
}
```

**Step 2: Write the failing tests** for all five typed responses. Reference for shape:

- `SingleChoiceResponse` — `fromArray(array $data)`: `['type' => 'single_choice', 'optionId' => string]`
- `MultiSelectResponse` — `fromArray(array $data)`: `['type' => 'multi_select', 'optionIds' => list<string>]` (≥1 correcta)
- `TrueFalseResponse` — `fromArray(array $data)`: `['type' => 'true_false', 'correct' => bool]`
- `MatchingResponse` — `fromArray(array $data)`: `['type' => 'matching', 'pairs' => list<array{leftId: string, rightId: string}>]` (≥1 par, leftIds únicos)
- `OrderingResponse` — `fromArray(array $data)`: `['type' => 'ordering', 'itemIds' => list<string>]` (≥2, ids únicos)

Tests clave (ejemplo `SingleChoiceResponse`):

```php
it('construye una respuesta de seleccion unica con su opcion correcta', function (): void {
    $response = SingleChoiceResponse::fromArray([
        'type' => 'single_choice',
        'optionId' => '1',
    ]);

    expect($response->toArray())->toBe([
        'type' => 'single_choice',
        'optionId' => '1',
    ]);
});

it('rechaza una respuesta de seleccion unica sin opcion correcta', function (): void {
    expect(fn () => SingleChoiceResponse::fromArray(['optionId' => '']))
        ->toThrow(InvalidQuestion::class);
});

it('rechaza ids duplicados en respuestas de opcion multiple', function (): void {
    expect(fn () => MultiSelectResponse::fromArray(['optionIds' => ['1', '1', '2']]))
        ->toThrow(InvalidQuestion::class);
});

it('rechaza respuestas de ordenamiento con menos de dos items o ids duplicados', function (): void {
    expect(fn () => OrderingResponse::fromArray(['itemIds' => ['a']]))
        ->toThrow(InvalidQuestion::class);
    expect(fn () => OrderingResponse::fromArray(['itemIds' => ['a', 'a']]))
        ->toThrow(InvalidQuestion::class);
});

it('rechaza pares de asociacion con lado izquierdo duplicado', function (): void {
    expect(fn () => MatchingResponse::fromArray(['pairs' => [
        ['leftId' => 'l1', 'rightId' => 'r1'],
        ['leftId' => 'l1', 'rightId' => 'r2'],
    ]]))->toThrow(InvalidQuestion::class);
});

it('construye una respuesta verdadero o falso', function (): void {
    $response = TrueFalseResponse::fromArray(['correct' => true]);
    expect($response->toArray())->toBe(['type' => 'true_false', 'correct' => true]);
});
```

Pruebas adicionales: cada `fromArray` rechaza tipo incorrecto en `type` (if isset y !== propio), claves extra/desconocidas, valores no strings.

**Step 3: Run tests to verify they fail**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\\vr506\\EDUDRIVE\\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/Entities/QuestionResponseTest.php`
Expected: FAIL (classes not found).

**Step 4: Implement the five response classes.** Each validates shape in `fromArray` (type check + required keys + no unknown keys + uniqueness rules) throwing `InvalidQuestion::create()`, and exposes `toArray()` returning the canonical form. `Type` enum value helper:

```php
private static function assertType(array $data, QuestionType $expected): void
{
    if (($data['type'] ?? null) !== $expected->value) {
        throw InvalidQuestion::create();
    }
}
```

`SingleChoiceResponse` full example:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities\Responses;

use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidQuestion;

final readonly class SingleChoiceResponse implements QuestionResponse
{
    private function __construct(
        public string $optionId,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        if (($data['type'] ?? null) !== QuestionType::SingleChoice->value) {
            throw InvalidQuestion::create();
        }

        $optionId = $data['optionId'] ?? null;
        if (! is_string($optionId) || trim($optionId) === '') {
            throw InvalidQuestion::create();
        }

        return new self(trim($optionId));
    }

    /** @return array{type: string, optionId: string} */
    public function toArray(): array
    {
        return [
            'type' => QuestionType::SingleChoice->value,
            'optionId' => $this->optionId,
        ];
    }
}
```

`OrderingResponse` (validación de duplicados y mínimo 2):

```php
/** @param array<string, mixed> $data */
public static function fromArray(array $data): self
{
    if (($data['type'] ?? null) !== QuestionType::Ordering->value) {
        throw InvalidQuestion::create();
    }

    $itemIds = $data['itemIds'] ?? null;
    if (! is_array($itemIds) || count($itemIds) < 2) {
        throw InvalidQuestion::create();
    }

    $ids = [];
    foreach ($itemIds as $itemId) {
        if (! is_string($itemId) || trim($itemId) === '') {
            throw InvalidQuestion::create();
        }
        $ids[] = trim($itemId);
    }

    if (count(array_unique($ids)) !== count($ids)) {
        throw InvalidQuestion::create();
    }

    return new self(ids: array_values($ids));
}
```

**Step 5: Run tests to verify they pass**

Run: same as Step 3. Expected: PASS.

**Step 6: Commit**

```bash
git add modules/Academic/Domain/Entities/Responses modules/Academic/Tests/Unit/Domain/Entities/QuestionResponseTest.php
git commit -m "feat(academic): add typed question responses"
```

---

### Task 5: Entidad `QuestionOption` (TDD)

**Files:**
- Create: `modules/Academic/Domain/Entities/QuestionOption.php`
- Test: ampliar `QuestionOptionTest` o incluir en `QuestionResponseTest.php`? Mejor crear `modules/Academic/Tests/Unit/Domain/Entities/QuestionOptionTest.php`

**Step 1: Write failing tests**

```php
it('crea una opcion de pregunta normalizada', function (): void {
    $option = QuestionOption::create(
        'opt-1',
        QuestionOptionId::fromString((string) Str::uuid()),
        1,
        '  Respuesta correcta  ',
    );

    expect($option->label())->toBe('Respuesta correcta')
        ->and($option->position())->toBe(1);
});

it('rechaza una opcion con texto vacio', function (): void {
    expect(fn () => QuestionOption::create('opt-1', QuestionOptionId::fromString((string) Str::uuid()), 1, '  '))
        ->toThrow(InvalidQuestion::class);
});

it('rechaza una posicion no positiva', function (): void {
    expect(fn () => QuestionOption::create('opt-1', QuestionOptionId::fromString((string) Str::uuid()), 0, 'opt'))
        ->toThrow(InvalidQuestion::class);
});

it('rechaza un lado no permitido para asociacion', function (): void {
    expect(fn () => QuestionOption::create('opt-1', QuestionOptionId::fromString((string) Str::uuid()), 1, 'opt', 'center'))
        ->toThrow(InvalidQuestion::class);
});
```

**Step 2: Run to verify fail.**

**Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use Modules\Academic\Domain\Exceptions\InvalidQuestion;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;

final class QuestionOption
{
    public const array ALLOWED_SIDES = ['left', 'right'];

    private function __construct(
        private readonly string $refId,
        private readonly QuestionOptionId $id,
        private readonly string $label,
        private readonly int $position,
        private readonly ?string $side,
    ) {}

    public static function create(
        string $refId,
        QuestionOptionId $id,
        int $position,
        string $label,
        ?string $side = null,
    ): self {
        $label = trim($label);
        if ($label === '' || strlen($label) > 500) {
            throw InvalidQuestion::create();
        }
        if ($position < 1) {
            throw InvalidQuestion::create();
        }
        if ($side !== null && ! in_array($side, self::ALLOWED_SIDES, true)) {
            throw InvalidQuestion::create();
        }

        return new self($refId, $id, $label, $position, $side);
    }

    public function refId(): string
    {
        return $this->refId;
    }

    public function id(): QuestionOptionId
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function side(): ?string
    {
        return $this->side;
    }
}
```

> Nota: `refId` es un código estable generado por el cliente (p.ej. `'opt-a'`, `'left-1'`) que el enunciado de respuesta referencia (optionId/itemId/leftId/rightId). El repositorio usa el UUID para la fila y el `refId` para la correspondencia con la respuesta. Mantenerlo simple: el cliente manda ids estables por opción y la respuesta referencia esos ids.

**Step 4: Run to verify pass.**

**Step 5: Commit**

```bash
git add modules/Academic/Domain/Entities/QuestionOption.php modules/Academic/Tests/Unit/Domain/Entities/QuestionOptionTest.php
git commit -m "feat(academic): add question option entity"
```

---

### Task 6: Agregado `Question` (TDD)

**Files:**
- Create: `modules/Academic/Domain/Aggregates/Question.php`
- Test: `modules/Academic/Tests/Unit/Domain/Aggregates/QuestionTest.php`

**Step 1: Design the aggregate contract**

```php
final class Question
{
    /** @param list<QuestionOption> $options */
    public static function create(
        QuestionId $id,
        QuestionType $type,
        CompetencyId $competencyId,
        string $prompt,
        int $score,
        QuestionResponse $response,
        array $options,
        ?string $explanation = null,
        array $media = [], // list<QuestionMedia>
    ): self;

    /** @param list<QuestionOption> $options */
    public static function restore(
        QuestionId $id,
        QuestionType $type,
        CompetencyId $competencyId,
        string $prompt,
        int $score,
        QuestionResponse $response,
        array $options,
        ?string $explanation = null,
        array $media = [],
    ): self;

    // getters: id(), type(), competencyId(), prompt(), score(), explanation(), media(), options(), response()
    // mutate: replace(...) → para update (cambia type/prompt/score/response/options/explanation/media)
}
```

Invariantes:
- `prompt` no vacío, ≤ 1000.
- `explanation` opcional ≤ 2000, se normaliza a null si vacío.
- `score` ≥ 1 (else `InvalidQuestionScore::create()`).
- `options` posiciones consecutivas desde 1; refIds únicos dentro de la pregunta.
- Para choice/multi: ≥2 opciones sin lado; cada opción de la respuesta debe existir en las opciones. Para matching: ≥2 opciones, y la respuesta `MatchingResponse` con pares cuyos left/right existan en las opciones correspondientes (side left/right). Para ordering: ≥2 ítems y cada itemId en opciones. Para true_false: sin opciones.
- `media`: cada elemento validado por `QuestionMedia::fromArray`. `media` solo permitido para `situational` (o al menos aceptado para cualquier tipo pero típicamente situacional; decisión: permitir solo `situational`).

Se define helper estático privado `validateOptionsForType(QuestionType $type, array $options, QuestionResponse $response)`.

```php
private static function validateOptionsForType(
    QuestionType $type,
    array $options,
    QuestionResponse $response,
): void {
    $refIds = array_map(static fn (QuestionOption $o): string => $o->refId(), $options);

    if (count($refIds) !== count(array_unique($refIds))) {
        throw InvalidQuestion::create();
    }

    foreach (self::positions($options) as $expected => $actual) {
        if ($actual !== $expected) {
            throw InvalidQuestion::create();
        }
    }

    if ($type === QuestionType::TrueFalse) {
        if ($options !== []) {
            throw InvalidQuestion::create();
        }
    }

    if ($type === QuestionType::SingleChoice) {
        if (count($options) < 2) {
            throw InvalidQuestion::create();
        }
        self::assertRefExists($refIds, $response->optionId);
    }

    if ($type === QuestionType::MultiSelect) {
        if (count($options) < 2) {
            throw InvalidQuestion::create();
        }
        foreach ($response->optionIds as $id) {
            self::assertRefExists($refIds, $id);
        }
    }

    if ($type === QuestionType::Matching) {
        if (count($options) < 2) {
            throw InvalidQuestion::create();
        }
        $leftIds = array_map(static fn (QuestionOption $o): string => $o->refId(), array_filter(
            $options,
            static fn (QuestionOption $o): bool => $o->side() === 'left',
        ));
        foreach ($response->pairs as $pair) {
            self::assertRefExists($leftIds, $pair['leftId']);
        }
    }

    if ($type === QuestionType::Ordering) {
        if (count($options) < 2) {
            throw InvalidQuestion::create();
        }
        foreach ($response->itemIds as $id) {
            self::assertRefExists($refIds, $id);
        }
    }

    if ($type === QuestionType::Situational) {
        // reutiliza la validación de la respuesta interna: delegamos al response subyacente
        // y exigimos tener media.
    }
}
```

> **Situational simplification (YAGNI):** para ENG-030 el tipo `situational` se modela igual que un `single_choice`/`multi_select`/etc. pero exige al menos un ítem de `media` y su `response` es una de las respuestas tipadas. Para no duplicar la lógica, el `response` interno de `situational` es cualquier `QuestionResponse` con `type` propio de los otros 5 y el enum del agregado es `situational`. En `QuestionResponse` para `situational` se serializa como `['type' => 'single_choice'|..., ...]` (el `type` interno) — el handler/HTTP expone `type: 'situational'` arriba. Dada la complejidad extra, se simplifica: `situational` usa una de las 5 respuestas internas y exige `media` no vacío + al menos 1 media. Validación:

```php
if ($type === QuestionType::Situational && $media === []) {
    throw InvalidQuestion::create();
}
```

**Step 2: Write failing tests** — cubrir:
- crear pregunta single_choice válida → getters correctos.
- puntaje 0 → `InvalidQuestionScore`.
- prompt vacío → `InvalidQuestion`.
- respuesta cuyo optionId no está entre las opciones → `InvalidQuestion`.
- opciones con posiciones no consecutivas → `InvalidQuestion`.
- refIds duplicados → `InvalidQuestion`.
- true_false sin opciones OK; single_choice con <2 opciones → `InvalidQuestion`.
- matching con par leftId no existente → `InvalidQuestion`.
- ordering con itemId no existente → `InvalidQuestion`.
- media con URL no HTTPS → `InvalidQuestion`.
- `situational` sin media → `InvalidQuestion`.
- restore reconstruye el agregado igual (round-trip de state).
- replace() permite cambiar prompt/score/response/options de forma atómica (cambiar de single_choice a multi_select correcto).

**Step 3: Run to verify fail.**

**Step 4: Implement `Question`** (validaciones en `create`/`restore`/`replace` vía los helpers estáticos; constructor privado; getters; `replace(...)` crea nueva instancia o muta in-place con las mismas validaciones — preferir in-place para que el repositorio guarde el mismo agregado).

Nota: `restore` NO debe validar rigurosamente contra opciones; debe reconstruir el estado tal cual (aunque sí valida estructura básica con los mismos helpers para detectar corrupción persistida y lanzar `InvalidQuestion`, consistente con "no ocultar corrupción persistida"). Para simplificar: `restore` usa los mismos helpers de validación pero salta la validación de `media` y el chequeo de refIds contra la respuesta cuando los datos provienen de persistencia — sin embargo, por consistencia con el resto del módulo (CourseVersion/UnitContent), `restore` valida estructura y deja pasar. Decisión final: `restore` invalida con `InvalidQuestion` si la estructura es incoherente (rechaza corrupción persistida), igual que `UnitContent`.

**Step 5: Run to verify pass.**

**Step 6: Commit**

```bash
git add modules/Academic/Domain/Aggregates/Question.php modules/Academic/Tests/Unit/Domain/Aggregates/QuestionTest.php
git commit -m "feat(academic): add question aggregate with typed response validation"
```

---

### Task 7: Repositorio de dominio

**Files:**
- Create: `modules/Academic/Domain/Repositories/QuestionRepository.php`

**Step 1: Write the interface**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Repositories;

use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\QuestionId;

interface QuestionRepository
{
    public function save(Question $question): void;

    public function findById(QuestionId $id): ?Question;

    /** @return list<Question> */
    public function all(?CompetencyId $competencyId = null): array;

    public function delete(QuestionId $id): void;
}
```

**Step 2: Commit**

```bash
git add modules/Academic/Domain/Repositories/QuestionRepository.php
git commit -m "feat(academic): add question repository contract"
```

---

### Task 8: Factory de respuesta tipada

**Files:**
- Create: `modules/Academic/Application/Services/QuestionResponseFactory.php`

**Step 1: Write the factory**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Services;

use Modules\Academic\Domain\Entities\Responses\MatchingResponse;
use Modules\Academic\Domain\Entities\Responses\MultiSelectResponse;
use Modules\Academic\Domain\Entities\Responses\OrderingResponse;
use Modules\Academic\Domain\Entities\Responses\QuestionResponse;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Entities\Responses\TrueFalseResponse;
use Modules\Academic\Domain\Exceptions\InvalidQuestion;

final readonly class QuestionResponseFactory
{
    /** @param array<string, mixed> $data */
    public static function fromPayload(string $type, array $data): QuestionResponse
    {
        $data = ['type' => $type] + $data;

        return match ($type) {
            'single_choice' => SingleChoiceResponse::fromArray($data),
            'multi_select' => MultiSelectResponse::fromArray($data),
            'true_false' => TrueFalseResponse::fromArray($data),
            'matching' => MatchingResponse::fromArray($data),
            'ordering' => OrderingResponse::fromArray($data),
            'situational' => self::situationalFromPayload($data),
            default => throw InvalidQuestion::create(),
        };
    }

    /** @param array<string, mixed> $data */
    private static function situationalFromPayload(array $data): QuestionResponse
    {
        $innerType = $data['response_type'] ?? $data['type'] ?? null;
        $inner = is_string($innerType) ? self::fromPayload($innerType, $data['response'] ?? []) : throw InvalidQuestion::create();
        $inner['media'] ??= null;

        // form canónica: envolver el response interno sin cambiar su tipo interno
        return new class($inner) implements QuestionResponse
        {
            /** @param QuestionResponse $inner */
            public function __construct(private QuestionResponse $inner) {}

            public function toArray(): array
            {
                $payload = $this->inner->toArray();
                unset($payload['type']);

                return $payload;
            }
        };
    }
}
```

> **Simplificación situacional (YAGNI):** el `response` JSONB para `situational` se guarda como el payload interno (la respuesta de uno de los 5 tipos, sin tipo envuelto): `['optionId' => 'opt-a']`, `['optionIds' => [...]]`, etc. El agregado `Question` guarda `type` = `situational` y el repositorio serializa `response` como el `toArray()` interno (que ya NO incluye `type` para situacional porque el anonymous class lo elimina). El handler de aplicación recién expone `correct` en su forma canónica. Si esta etapa de wrapping se vuelve confusa, la alternativa: `Question::media()` no vacío valida `situational`, y para el response internamente el agregado recibe la `QuestionResponse` tipada de los 5 tipos; al serializar guardamos `$response->toArray()` sin `type` y el `type` de la pregunta es `situational` en `academic_questions.type`. En el GET, `correct` se envuelve con el tipo real de respuesta. **Implementación pragmática:** en el agregado, `response()` devuelve la `QuestionResponse` del tipo interno real (single_choice, etc.) y en el repositorio al persistir `situational` se guarda `json_encode(response.toArray())` (que incluye su `type`) — es lo más simple y consistente. En el `QuestionResponse::toArray()` de los tipos internos ya se incluye `type`; eso no afecta. Para el GET de una pregunta `situational` se devuelve el `type` de la pregunta (`situational`) con `correct` = respuesta interna completa.

> **Decisión final (registrada):** `Question::create/restore` reciben la `QuestionResponse` ya tipada (incluyendo para `situational` su respuesta interna). El repositorio persiste `response` = `json_decode(json_encode($response->toArray()), true)` para todos. El `type` de columna siempre es del enum del agregado. Para `situational` la respuesta interna puede llevar su propio `type`. La validación de `situational` exige `media` no vacío. Simplicidad > perfección.

**Step 2: Commit**

```bash
git add modules/Academic/Application/Services/QuestionResponseFactory.php
git commit -m "feat(academic): add question response factory"
```

---

### Task 9: Commandos y queries

**Files:**
- Create: `modules/Academic/Application/Commands/CreateQuestionCommand.php`
- Create: `modules/Academic/Application/Commands/UpdateQuestionCommand.php`
- Create: `modules/Academic/Application/Commands/DeleteQuestionCommand.php`
- Create: `modules/Academic/Application/Queries/GetQuestionQuery.php`
- Create: `modules/Academic/Application/Queries/ListQuestionsQuery.php`
- Create: `modules/Academic/Application/Exceptions/QuestionNotFound.php`

**Step 1: Write commands** (patrón `CreateCompetencyCommand`)

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CreateQuestionCommand implements Command
{
    /**
     * @param array<string, mixed> $response
     * @param list<array{refId: string, label: string, side?: string|null}> $options
     * @param list<array{type: string, url: string}> $media
     */
    public function __construct(
        public string $competencyId,
        public string $type,
        public string $prompt,
        public int $score,
        public array $response,
        public array $options = [],
        public ?string $explanation = null,
        public array $media = [],
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class UpdateQuestionCommand implements Command
{
    /**
     * @param array<string, mixed> $response
     * @param list<array{refId: string, label: string, side?: string|null}> $options
     * @param list<array{type: string, url: string}> $media
     */
    public function __construct(
        public string $questionId,
        public string $type,
        public string $prompt,
        public int $score,
        public array $response,
        public array $options = [],
        public ?string $explanation = null,
        public array $media = [],
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class DeleteQuestionCommand implements Command
{
    public function __construct(public string $questionId) {}
}
```

**Step 2: Write queries**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetQuestionQuery implements Query
{
    public function __construct(public string $questionId) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class ListQuestionsQuery implements Query
{
    public function __construct(public ?string $competencyId = null) {}
}
```

**Step 3: Write QuestionNotFound** (patrón `CourseNotFound`)

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class QuestionNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: sprintf('No existe una pregunta con el identificador %s.', $id),
            errorCode: 'QUESTION_NOT_FOUND',
            statusCode: 404,
        );
    }
}
```

**Step 4: Commit**

```bash
git add modules/Academic/Application/Commands modules/Academic/Application/Queries modules/Academic/Application/Exceptions/QuestionNotFound.php
git commit -m "feat(academic): add question commands, queries and not-found exception"
```

---

### Task 10: Handlers de aplicación (TDD)

**Files:**
- Create: `modules/Academic/Application/UseCases/CreateQuestionHandler.php`
- Create: `modules/Academic/Application/UseCases/UpdateQuestionHandler.php`
- Create: `modules/Academic/Application/UseCases/DeleteQuestionHandler.php`
- Create: `modules/Academic/Application/UseCases/GetQuestionHandler.php`
- Create: `modules/Academic/Application/UseCases/ListQuestionsHandler.php`
- Create: `modules/Academic/Application/Responses/QuestionResponse.php`
- Create: `modules/Academic/Application/Responses/QuestionListItemResponse.php`
- Test: `modules/Academic/Tests/Unit/Application/QuestionHandlerTest.php`
- Modify: `tests/Pest.php` (helper `persistedCompetencyForQuestions`)

**Step 1: Add shared helper to `tests/Pest.php`**

Para tests unitarios de aplicación necesitamos persistir una competencia (el handler valida competencia existente). Añadir helper:

```php
function persistedCompetencyForQuestions(): string
{
    $repository = app(\Modules\Academic\Domain\Repositories\CompetencyRepository::class);
    $id = \Modules\Academic\Domain\ValueObjects\CompetencyId::fromString((string) Str::uuid());
    $repository->save(\Modules\Academic\Domain\Aggregates\Competency::create(
        $id,
        \Modules\Academic\Domain\ValueObjects\CompetencyCode::fromString('CQ-'.strtoupper((string) Str::random(4))),
        'Competencia de prueba para preguntas',
        'Competencia utilizada únicamente en pruebas de preguntas.',
        \Modules\Academic\Domain\Enums\CompetencyCategory::Signalization,
        \Modules\Academic\Domain\Enums\MasteryLevel::Basic,
    ));

    return $id->value();
}
```

Verificar en `CompetencyCategory`/`MasteryLevel` cuáles son los casos de enum disponibles (Signalization/Basic puede variar) — adaptar según `modules/Academic/Domain/Enums/CompetencyCategory.php` y `MasteryLevel.php`. Si hay valores distintos, usar los que existan.

**Step 2: Write Application responses**

`QuestionResponse` (Application):

```php
/** @param list<array{refId: string, label: string, position: int, side: string|null}> $options
 *  @param array<string, mixed> $correct
 *  @param list<array{type: string, url: string}> $media */
private function __construct(
    public string $id,
    public string $type,
    public string $competencyId,
    public string $prompt,
    public int $score,
    public ?string $explanation,
    public array $options,
    public array $correct,
    public array $media,
) {}

public static function fromQuestion(Question $question): self { ... }

/** @return array{id: string, type: string, competency_id: string, prompt: string, score: int, explanation: ?string, options: list<...>, correct: array<string, mixed>, media: list<...>} */
public function toArray(): array { ... }
```

`QuestionListItemResponse` (listado): `id`, `type`, `competency_id`, `prompt`, `score` — sin opciones ni correct.

**Step 3: Write failing handler tests** — dobles en memoria:

```php
final class InMemoryQuestionRepository implements QuestionRepository
{
    /** @var array<string, Question> */
    public array $questions = [];
    public int $saveCalls = 0;

    public function save(Question $question): void
    {
        $this->saveCalls++;
        $this->questions[$question->id()->value()] = $question;
    }

    public function findById(QuestionId $id): ?Question
    {
        return $this->questions[$id->value()] ?? null;
    }

    /** @return list<Question> */
    public function all(?CompetencyId $competencyId = null): array
    {
        return array_values(array_filter(
            $this->questions,
            static fn (Question $q): bool => $competencyId === null || $q->competencyId()->equals($competencyId),
        ));
    }

    public function delete(QuestionId $id): void
    {
        unset($this->questions[$id->value()]);
    }
}
```

Digamos que `Question::competencyId(): CompetencyId`.

Tests:
- crear pregunta single_choice válida → `QuestionResponse`, `saveCalls === 1`, response tiene `correct.optionId`.
- crear con competencia inexistente → 404 `QUESTION_NOT_FOUND` (competencia).
- crear con puntaje inválido → `InvalidQuestionScore` y NO guarda.
- actualizar pregunta existente (cambiar prompt/score) → `saveCalls === 2` (una por cada save: la creación y el update), response refleja cambios.
- actualizar pregunta inexistente → `QuestionNotFound`.
- eliminar pregunta existente → `delete` la quita del repo.
- eliminar pregunta inexistente → `QuestionNotFound`.
- listar sin filtro → todas; con filtro por competencia → solo las de esa competencia.
- detalle → devuelve la pregunta con su `correct`.
- detalle inexistente → `QuestionNotFound`.
- errores de dominio del agregado se propagan (score/prompt/media) y NO mutan el repo (sin save).

**Step 4: Run to verify fail.**

**Step 5: Implement handlers.** Cada handler:

`CreateQuestionHandler`:
```php
final readonly class CreateQuestionHandler
{
    public function __construct(
        private QuestionRepository $questions,
        private CompetencyRepository $competencies,
    ) {}

    public function handle(CreateQuestionCommand $command): QuestionResponse
    {
        $competencyId = CompetencyId::fromString($command->competencyId);
        if ($this->competencies->findById($competencyId) === null) {
            throw QuestionNotFound::withId($command->competencyId);
        }

        $question = Question::create(
            QuestionId::fromString((string) Str::uuid()),
            QuestionType::from($command->type),
            $competencyId,
            $command->prompt,
            $command->score,
            QuestionResponseFactory::fromPayload($command->type, $command->response),
            array_map($this->optionMapper(), $command->options),
            $command->explanation,
            array_map(static fn (array $m): QuestionMedia => QuestionMedia::fromArray($m), $command->media),
        );
        $this->questions->save($question);

        return QuestionResponse::fromQuestion($question);
    }
}
```

helper optionMapper:
```php
/** @return callable(array{refId: string, label: string, side?: string|null, position?: int}): QuestionOption */
private function optionMapper(): callable
{
    return static fn (array $option, int $index): QuestionOption => QuestionOption::create(
        refId: (string) $option['refId'],
        id: QuestionOptionId::fromString((string) Str::uuid()),
        position: (int) ($option['position'] ?? ($index + 1)),
        label: (string) $option['label'],
        side: isset($option['side']) ? (string) $option['side'] : null,
    );
}
```

> Nota: el agregado valida posiciones consecutivas desde 1; el handler asigna posición = índice+1 salvo que el request la traiga explícita. Simplificar: posición = índice+1 siempre (la request no manda posición; el orden del array define el orden canónico). Se elimina `position` del request mapping de opciones para YAGNI.

`UpdateQuestionHandler`:
```php
public function handle(UpdateQuestionCommand $command): QuestionResponse
{
    $id = QuestionId::fromString($command->questionId);
    $question = $this->questions->findById($id);
    if ($question === null) {
        throw QuestionNotFound::withId($command->questionId);
    }

    $question->replace(
        QuestionType::from($command->type),
        $command->prompt,
        $command->score,
        QuestionResponseFactory::fromPayload($command->type, $command->response),
        array_map($this->optionMapper(), $command->options),
        $command->explanation,
        array_map(static fn (array $m): QuestionMedia => QuestionMedia::fromArray($m), $command->media),
    );
    $this->questions->save($question);

    return QuestionResponse::fromQuestion($question);
}
```

`DeleteQuestionHandler`:
```php
public function handle(DeleteQuestionCommand $command): void
{
    $id = QuestionId::fromString($command->questionId);
    if ($this->questions->findById($id) === null) {
        throw QuestionNotFound::withId($command->questionId);
    }

    $this->questions->delete($id);
}
```

`GetQuestionHandler`:
```php
public function handle(GetQuestionQuery $query): QuestionResponse
{
    $question = $this->questions->findById(QuestionId::fromString($query->questionId));
    if ($question === null) {
        throw QuestionNotFound::withId($query->questionId);
    }

    return QuestionResponse::fromQuestion($question);
}
```

`ListQuestionsHandler`:
```php
/** @return list<QuestionListItemResponse> */
public function handle(ListQuestionsQuery $query): array
{
    $competencyId = $query->competencyId === null ? null : CompetencyId::fromString($query->competencyId);

    return array_map(
        static fn (Question $q): QuestionListItemResponse => QuestionListItemResponse::fromQuestion($q),
        $this->questions->all($competencyId),
    );
}
```

**Step 6: Run to verify pass.**

**Step 7: Commit**

```bash
git add modules/Academic/Application/UseCases modules/Academic/Application/Responses tests/Pest.php modules/Academic/Tests/Unit/Application/QuestionHandlerTest.php
git commit -m "feat(academic): add question application use cases"
```

---

### Task 11: Repositorio Eloquent (TDD + integración)

**Files:**
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/QuestionModel.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/QuestionOptionModel.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentQuestionRepository.php`
- Test: `modules/Academic/Tests/Integration/EloquentQuestionRepositoryTest.php`

**Step 1: Write QuestionModel**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class QuestionModel extends Model
{
    protected $table = 'academic_questions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<QuestionOptionModel, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOptionModel::class, 'question_id');
    }

    protected function casts(): array
    {
        return [
            'score' => 'int',
            'media' => 'array',
            'response' => 'array',
        ];
    }
}
```

**Step 2: Write QuestionOptionModel** (tabla `academic_question_options`, uuid PK, guarded=[], casts `position` int, side string).

**Step 3: Write failing integration tests**

Cubrir:
- guarda y reconstruye una pregunta single_choice con su snapshot canónico de respuesta (round-trip).
- reconstruye matching con side left/right en opciones.
- lista ordenada y filtra por competencia.
- elimina en cascada las opciones al borrar la pregunta.
- FK valida competencia inexistente (no traduce QueryException de FK a un código de negocio — verificar que lanza QueryException, como test "no oculta corrupcion persistida").
- carga sin N+1: 1 query por pregunta + 1 por opciones (assert `DB::enableQueryLog()`).
- response corrupto persistido → revierte con `InvalidQuestion` (no ocultar corrupción).

Helpers en el archivo de test o `tests/Pest.php`:
```php
function persistedQuestionCompetency(): Competency
{
    $repo = app(\Modules\Academic\Domain\Repositories\CompetencyRepository::class);
    $competency = \Modules\Academic\Domain\Aggregates\Competency::create(
        \Modules\Academic\Domain\ValueObjects\CompetencyId::fromString((string) Illuminate\Support\Str::uuid()),
        \Modules\Academic\Domain\ValueObjects\CompetencyCode::fromString('CQ-'.strtoupper((string) Illuminate\Support\Str::random(4))),
        'Competencia de preguntas',
        'Descripción de competencia de preguntas.',
        \Modules\Academic\Domain\Enums\CompetencyCategory::Signalization,
        \Modules\Academic\Domain\Enums\MasteryLevel::Basic,
    );
    $repo->save($competency);

    return $competency;
}
```
(adaptar casos de enum a los existentes)

**Step 4: Run to verify fail.**

**Step 5: Implement EloquentQuestionRepository**

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Academic\Application\Services\QuestionResponseFactory;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionMedia;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\QuestionModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\QuestionOptionModel;

final readonly class EloquentQuestionRepository implements QuestionRepository
{
    public function save(Question $question): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($question): void {
            $model = QuestionModel::query()->updateOrCreate(
                ['id' => $question->id()->value()],
                [
                    'competency_id' => $question->competencyId()->value(),
                    'type' => $question->type()->value,
                    'prompt' => $question->prompt(),
                    'explanation' => $question->explanation(),
                    'score' => $question->score(),
                    'media' => array_map(
                        static fn (QuestionMedia $media): array => $media->toArray(),
                        $question->media(),
                    ),
                    'response' => $question->response()->toArray(),
                ],
            );

            $model->options()->delete();

            foreach ($question->options() as $option) {
                QuestionOptionModel::query()->create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'question_id' => $model->id,
                    'ref_id' => $option->refId(),
                    'side' => $option->side(),
                    'label' => $option->label(),
                    'position' => $option->position(),
                ]);
            }
        });
    }

    public function findById(QuestionId $id): ?Question
    {
        $model = QuestionModel::query()->with('options')->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<Question> */
    public function all(?CompetencyId $competencyId = null): array
    {
        $builder = QuestionModel::query()->with('options');
        if ($competencyId !== null) {
            $builder->where('competency_id', $competencyId->value());
        }

        return array_values(
            $builder->orderBy('created_at')->get()
                ->map(fn (QuestionModel $model): Question => $this->toDomain($model))
                ->all(),
        );
    }

    public function delete(QuestionId $id): void
    {
        QuestionModel::query()->where('id', $id->value())->delete();
    }

    private function toDomain(QuestionModel $model): Question
    {
        /** @var array<string, mixed> $response */
        $response = $model->getAttribute('response');

        /** @var list<array{type: string, url: string}> $media */
        $media = $model->getAttribute('media') ?? [];

        return Question::restore(
            QuestionId::fromString((string) $model->getAttribute('id')),
            QuestionType::from((string) $model->getAttribute('type')),
            CompetencyId::fromString((string) $model->getAttribute('competency_id')),
            (string) $model->getAttribute('prompt'),
            (int) $model->getAttribute('score'),
            QuestionResponseFactory::fromPayload((string) $model->getAttribute('type'), $response),
            $model->options->map(fn (QuestionOptionModel $option): QuestionOption => QuestionOption::create(
                refId: (string) $option->getAttribute('ref_id'),
                id: QuestionOptionId::fromString((string) $option->getAttribute('id')),
                position: (int) $option->getAttribute('position'),
                label: (string) $option->getAttribute('label'),
                side: $option->getAttribute('side') === null ? null : (string) $option->getAttribute('side'),
            ))->all(),
            $model->getAttribute('explanation') === null ? null : (string) $model->getAttribute('explanation'),
            array_map(static fn (array $m): QuestionMedia => QuestionMedia::fromArray($m), $media),
        );
    }
}
```

> Añadir columna `ref_id` a la tabla `academic_question_options` en la migración (Task 1 ya creada; **actualizar Task 1**: incluir `$table->string('ref_id', 80);` + `unique(['question_id', 'ref_id'])`). IMPORTANTE: actualizar la migración antes de correr Task 11 (editar el archivo de Task 1).

**Step 6: Run integration tests to verify pass.**

**Step 7: Run the migration on the dev DB** (aplica sobre Postgres en el contenedor a la DB real, como hicimos en ENG-029):

```bash
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\\vr506\\EDUDRIVE\\edudrive-api:/var/www/html" edudrive-app php artisan migrate --force
```

**Step 8: Commit**

```bash
git add modules/Academic/Infrastructure/Persistence/Eloquent modules/Academic/Tests/Integration/EloquentQuestionRepositoryTest.php modules/Academic/Infrastructure/Persistence/Migrations
git commit -m "feat(academic): add eloquent question repository"
```

---

### Task 12: Registrar repo y handlers en el provider

**Files:**
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`

**Step 1: Añadir imports y bind + registrations**

En `register()`:
```php
$this->app->bind(QuestionRepository::class, EloquentQuestionRepository::class);
```

En `boot()` (MessageHandlerRegistry):
```php
$registry->register(CreateQuestionCommand::class, CreateQuestionHandler::class);
$registry->register(UpdateQuestionCommand::class, UpdateQuestionHandler::class);
$registry->register(DeleteQuestionCommand::class, DeleteQuestionHandler::class);
$registry->register(GetQuestionQuery::class, GetQuestionHandler::class);
$registry->register(ListQuestionsQuery::class, ListQuestionsHandler::class);
```

**Step 2: Run a smoke check** (los Feature tests de Task 14 lo validarán; por ahora confirmar que la app bootea)

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\\vr506\\EDUDRIVE\\edudrive-api:/var/www/html" edudrive-app php artisan route:list --path=academic/questions` — aún sin rutas, no debe crashear.

**Step 3: Commit**

```bash
git add modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php
git commit -m "feat(academic): register question handlers in provider"
```

---

### Task 13: Permisos y grants

**Files:**
- Modify: `modules/Authorization/Domain/Enums/Permission.php`
- Modify: `modules/Authorization/Domain/Services/RolePermissions.php`

**Step 1: Add permission cases**

```php
case ManageQuestions = 'questions.manage';
case ViewQuestions = 'questions.view';
```

**Step 2: Add grants**

- SuperAdmin: añadir ambos a la lista.
- InstitutionalAdmin/Teacher/Student: añadir `ViewQuestions`.

**Step 3: Update role permission tests** (`modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`): añadir asserts de `ManageQuestions`/`ViewQuestions` por rol, siguiendo el bloque de courses (líneas 28-38).

**Step 4: Run the auth unit tests**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\\vr506\\EDUDRIVE\\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Authorization`
Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Authorization/Domain/Enums/Permission.php modules/Authorization/Domain/Services/RolePermissions.php modules/Authorization/Tests
git commit -m "feat(authorization): add question bank permissions"
```

---

### Task 14: Presentación HTTP (controller, requests, rutas) (TDD)

**Files:**
- Create: `modules/Academic/Presentation/Http/Controllers/QuestionController.php`
- Create: `modules/Academic/Presentation/Http/Requests/CreateQuestionRequest.php`
- Create: `modules/Academic/Presentation/Http/Requests/UpdateQuestionRequest.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Test: `modules/Academic/Tests/Feature/QuestionTest.php`

**Step 1: Write CreateQuestionRequest** (validación temprana, siguiendo `CreateCompetencyRequest`, con `after` para validar la forma de respuesta — reutilizar la lógica de dominio en vez de duplicar toda la validación; mínimo: tipos reconocidos, prompt, score, media URLs):

```php
final class CreateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'competency_id' => ['required', 'string', 'uuid'],
            'type' => ['required', 'string', new Enum(QuestionType::class)],
            'prompt' => ['required', 'string', 'max:1000'],
            'score' => ['required', 'integer', 'min:1'],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'options' => ['sometimes', 'array'],
            'options.*.ref_id' => ['required_with:options', 'distinct', 'string', 'max:80'],
            'options.*.label' => ['required_with:options', 'string', 'max:500'],
            'options.*.side' => ['nullable', 'string', new Rule\In(['left', 'right'])],
            'media' => ['sometimes', 'array'],
            'media.*.type' => ['required_with:media', 'string', new Rule\In(['image', 'video', 'audio'])],
            'media.*.url' => ['required_with:media', 'string', 'url'],
            'response' => ['required', 'array'],
        ];
    }
}
```

> La consistencia total (correct ∈ opciones, posiciones, sin duplicados) la valida el dominio en el agregado; la request evita que lleguen payloads groseramente malformados. `response` se pasa tal cual; el handler lo parsea con la factory.

**Step 2: Write UpdateQuestionRequest** — igual que create pero con `question_id` opcional y sin `competency_id` (no se puede re-anclar; o se permite, decisión: **no se permite re-anclar** — la pregunta permanece en su competencia). `rules()` iguales.

**Step 3: Write QuestionController** (patrón `CompetencyController`)

```php
final class QuestionController
{
    public function syncList(...) // no; usamos métodos: index, store, update, destroy, show

    public function index(ListQuestionsQuery ...) // vía QueryBus
    public function store(CreateQuestionRequest $request, CommandBus $commandBus): JsonResponse
    public function show(string $questionId, QueryBus $queryBus): JsonResponse
    public function update(string $questionId, UpdateQuestionRequest $request, CommandBus $commandBus): JsonResponse
    public function destroy(string $questionId, CommandBus $commandBus): JsonResponse (204)
}
```

`store` devuelve `201`; `destroy` devuelve `204`; resto `200` con `{data: ...}`.

`index` lee `competency_id` de `$request->query('competency_id')`. `index` recibe `Request $request`.

**Step 4: Write routes**

```php
Route::middleware('permission:questions.view')->group(function (): void {
    Route::get('/questions', [QuestionController::class, 'index'])->name('questions.index');
    Route::get('/questions/{questionId}', [QuestionController::class, 'show'])
        ->whereUuid('questionId')
        ->name('questions.show');
});

Route::middleware('permission:questions.manage')->group(function (): void {
    Route::post('/questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::put('/questions/{questionId}', [QuestionController::class, 'update'])
        ->whereUuid('questionId')
        ->name('questions.update');
    Route::delete('/questions/{questionId}', [QuestionController::class, 'destroy'])
        ->whereUuid('questionId')
        ->name('questions.destroy');
});
```

**Step 5: Write failing Feature tests** (`QuestionTest.php`), reusando `actingAsRole(Role::Student)` (ya existe en `tests/Pest.php` desde ENG-029) y `persistedCompetencyForQuestions()`:

- crea una pregunta de selección única → 201, `data.id`, `data.type`, `data.correct.optionId`.
- crea una pregunta true_false sin opciones → 201.
- valida puntaje 0 → 422 con errores de validación.
- rechaza crear con competencia inexistente → 404 `QUESTION_NOT_FOUND`.
- lista filtrada por `competency_id` → solo esa.
- obtiene detalle → `data.correct` presente.
- actualiza prompt/score → 200 refleja cambios.
- elimina → 204 y listado ya no la incluye.
- 404 al obtener/actualizar/eliminar pregunta inexistente → `QUESTION_NOT_FOUND`.
- 401 sin token en cada endpoint.
- 403 sin `questions.manage` (estudiante) al crear; y ver que Student SÍ puede listar (view). Mutaciones inaccesibles.
- valida media URL no https → 422 (request `url` rule) o dominio (`INVALID_QUESTION`) si llega.

**Step 6: Run to verify fail.**

**Step 7: Implement controller/requests/rutas** conexión al dominio/application. Correr Feature tests a green.

**Step 8: Run the full Academic suite**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\\vr506\\EDUDRIVE\\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic`
Expected: all PASS (incluye 91 tests Feature previos + los nuevos).

**Step 9: Commit**

```bash
git add modules/Academic/Presentation modules/Academic/Tests/Feature/QuestionTest.php
git commit -m "feat(academic): expose question bank http api"
```

---

### Task 15: Validación final (Pint, PHPStan, suite completa, migrate, route:list)

**Files:** (ninguno nuevo)

**Step 1: Pint**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\\vr506\\EDUDRIVE\\edudrive-api:/var/www/html" edudrive-app vendor/bin/pint`
Expected: FIXED list (vacía o style issues corregidos).

**Step 2: PHPStan**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\\vr506\\EDUDRIVE\\edudrive-api:/var/www/html" edudrive-app vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
Expected: `[OK] No errors`.

**Step 3: Suite completa** (root + módulos + Academic)

Run:
1. `... php artisan test` (root tests)
2. `... php artisan test modules/Academic`
3. `... php artisan test modules/Authorization modules/Identity modules/Organization modules/Audit modules/Foundation`
Expected: all PASS, sin FAIL.

**Step 4: migrate + migrate:status sobre dev DB**

Run: `... php artisan migrate --force` y luego `... php artisan migrate:status`
Expected: `academic_questions` y `academic_question_options` con estado `Ran`.

**Step 5: route:list**

Run: `... php artisan route:list --path=academic/questions`
Expected: 5 rutas (index, store, show, update, destroy en `api/v1/academic/questions`).

---

### Task 16: Documentación (roadmap + ENG-LOG)

**Files:**
- Modify: `docs/roadmap/ENG-000-roadmap-tecnico-backend.md`
- Modify: `docs/engineering/ENG-LOG.md`

**Step 1: Roadmap** — marcar ENG-030 "Completado" con nota similar a ENG-028 (sección 11 o 12), y añadir entrada de changelog `1.10.0` en "Control de cambios".

**Step 2: ENG-LOG** — entrada `## 2026-08-10 — IMP-030 (Cierre de ENG-030 — Banco de preguntas)` con secciones Completado / Validaciones / Estado: Finalizado. Validaciones: pint, phpstan, nº total de pruebas/aserciones, counts de rutas (5) y migraciones (2 tablas).

**Step 3: Commit**

```bash
git add docs/roadmap/ENG-000-roadmap-tecnico-backend.md docs/engineering/ENG-LOG.md
git commit -m "docs(engineering): update roadmap and log for ENG-030 completion"
```

---

## Notas de riesgos y decisiones

- **refId en opciones:** para que la respuesta (`correct.optionId`) referencie opciones de forma estable sin depender de UUIDs de fila, las opciones llevan un `ref_id` estable (p.ej. `opt-a`, `left-1`) definido por el cliente. El agregado valida que la respuesta referencie refIds existentes. La tabla `academic_question_options` debe incluir `ref_id` — se agrega en la migración de la Task 1 (ver nota en Task 11).
- **`situational`:** simplificado a "respuesta interna de uno de los 5 tipos + media obligatoria". El `type` de la columna es `situational`; el `response` JSONB contiene el response interno (que puede incluir su propio `type` o no — decisión: el response interno se serializa tal cual con su `type` interno; la factory para `situational` envuelve).
- **Claves desconocidas en response:** cada clase `...Response::fromArray` rechaza claves desconocidas (patrón `ensureKeys` de los bloques de contenido) para evitar payloads corruptos.
- **`delete` y FK:** la FK `question_id` con `cascadeOnDelete` borra opciones automáticamente; el repo borra la fila padre.
- **`response` JSONB vs `json`:** usar `json` (que en Postgres mapea a `json`) como en `academic_lesson_blocks`; si el proyecto usó `jsonb` en algún lado conectar con lo que esté en la migración de ENG-029 (que usó `jsonb`). Consistencia: usar `json` (mismo tipo que bloques) — el cast Eloquent `array` funciona igual. **Decisión:** usar `json` para `media` y `response`, emparejando `academic_lesson_blocks`.
- **Tests de integración** usan `RefreshDatabase` (lo provee `tests/TestCase`). Verificar que los tests de integración existentes usan `Pest::test` con `\Tests\TestCase`.

## Verificación cruzada al terminar cada tarea

- Los tests del módulo Academic siempre verdes (`php artisan test modules/Academic`).
- Ningún commit deja vetas de archivos sin formatear (correr pint antes de cada commit si se tocó PHP).