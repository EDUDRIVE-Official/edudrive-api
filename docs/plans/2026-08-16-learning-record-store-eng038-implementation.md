# ENG-038 - Learning Record Store interno Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Registrar como hechos inmutables (append-only) los eventos de aprendizaje que ya ocurren hoy en `Academic` (lección completada, examen enviado) en un nuevo módulo `Modules\Learning`, y exponer una consulta dedicada por inscripción.

**Architecture:** Nuevo módulo `Modules\Learning` con DDD completo (`LearningEvent` como entidad inmutable, `LearningEventRepository` en el dominio, infraestructura Eloquent). `Academic` depende de la abstracción `LearningEventRecorder` (interfaz de `Learning\Application`) para registrar eventos desde `CompleteLessonHandler` y `SubmitExamAttemptHandler` — mismo patrón que ya usa `Identity` con `AuditLogger` de `Audit`. `Learning` depende a su vez de `EnrollmentRepository`/`EnrollmentNotFound` de `Academic` para autorizar la consulta por pertenencia (dueño del enrollment o `enrollments.view`) — ver nota de acoplamiento bidireccional al final del plan.

**Tech Stack:** Laravel 12, Pest, Sanctum, `CommandBus`/`QueryBus` propios, PHP 8.2+, nuevo módulo `modules/Learning`.

**Diseño de referencia:** `docs/plans/2026-08-16-learning-record-store-eng038-design.md`

---

### Task 1: Dominio — `LearningEventId`, `LearningVerb`, `LearningEvent`

**Files:**
- Create: `modules/Learning/Domain/ValueObjects/LearningEventId.php`
- Create: `modules/Learning/Domain/ValueObjects/LearningVerb.php`
- Create: `modules/Learning/Domain/Entities/LearningEvent.php`
- Test: `modules/Learning/Tests/Unit/Domain/Entities/LearningEventTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Learning\Domain\Entities\LearningEvent;
use Modules\Learning\Domain\ValueObjects\LearningEventId;
use Modules\Learning\Domain\ValueObjects\LearningVerb;

it('crea un evento de aprendizaje inmutable con sus datos', function (): void {
    $id = LearningEventId::fromString((string) Str::uuid());
    $occurredAt = new DateTimeImmutable('2026-08-16T10:00:00+00:00');

    $event = LearningEvent::create(
        id: $id,
        enrollmentId: 'enrollment-1',
        userId: 'user-1',
        courseId: 'course-1',
        verb: LearningVerb::LessonCompleted,
        subjectId: 'lesson-1',
        occurredAt: $occurredAt,
        evidence: ['time_spent_minutes' => 12],
    );

    expect($event->id()->equals($id))->toBeTrue()
        ->and($event->enrollmentId())->toBe('enrollment-1')
        ->and($event->userId())->toBe('user-1')
        ->and($event->courseId())->toBe('course-1')
        ->and($event->verb())->toBe(LearningVerb::LessonCompleted)
        ->and($event->subjectId())->toBe('lesson-1')
        ->and($event->occurredAt())->toBe($occurredAt)
        ->and($event->evidence())->toBe(['time_spent_minutes' => 12]);
});

it('rechaza un identificador de evento que no es un uuid valido', function (): void {
    expect(fn () => LearningEventId::fromString('no-es-un-uuid'))
        ->toThrow(InvalidArgumentException::class);
});

it('expone los dos verbos soportados', function (): void {
    expect(LearningVerb::LessonCompleted->value)->toBe('lesson_completed')
        ->and(LearningVerb::ExamAttemptSubmitted->value)->toBe('exam_attempt_submitted');
});
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Learning/Tests/Unit/Domain/Entities/LearningEventTest.php`

Expected: FAIL porque `Modules\Learning\...` no existe todavía.

**Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Learning\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class LearningEventId
{
    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $value) !== 1) {
            throw new InvalidArgumentException('El identificador del evento de aprendizaje debe ser un UUID valido.');
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

```php
<?php

declare(strict_types=1);

namespace Modules\Learning\Domain\ValueObjects;

enum LearningVerb: string
{
    case LessonCompleted = 'lesson_completed';
    case ExamAttemptSubmitted = 'exam_attempt_submitted';
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Learning\Domain\Entities;

use DateTimeImmutable;
use Modules\Learning\Domain\ValueObjects\LearningEventId;
use Modules\Learning\Domain\ValueObjects\LearningVerb;

final readonly class LearningEvent
{
    /** @param array<string, mixed> $evidence */
    private function __construct(
        private LearningEventId $id,
        private string $enrollmentId,
        private string $userId,
        private string $courseId,
        private LearningVerb $verb,
        private string $subjectId,
        private DateTimeImmutable $occurredAt,
        private array $evidence,
    ) {}

    /** @param array<string, mixed> $evidence */
    public static function create(
        LearningEventId $id,
        string $enrollmentId,
        string $userId,
        string $courseId,
        LearningVerb $verb,
        string $subjectId,
        DateTimeImmutable $occurredAt,
        array $evidence,
    ): self {
        return new self($id, $enrollmentId, $userId, $courseId, $verb, $subjectId, $occurredAt, $evidence);
    }

    public function id(): LearningEventId
    {
        return $this->id;
    }

    public function enrollmentId(): string
    {
        return $this->enrollmentId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function courseId(): string
    {
        return $this->courseId;
    }

    public function verb(): LearningVerb
    {
        return $this->verb;
    }

    public function subjectId(): string
    {
        return $this->subjectId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /** @return array<string, mixed> */
    public function evidence(): array
    {
        return $this->evidence;
    }
}
```

**Step 4: Run test to verify it passes.** Expected: PASS (3 tests).

**Step 5: Commit**

```bash
git add modules/Learning/Domain/ValueObjects/LearningEventId.php modules/Learning/Domain/ValueObjects/LearningVerb.php modules/Learning/Domain/Entities/LearningEvent.php modules/Learning/Tests/Unit/Domain/Entities/LearningEventTest.php
git commit -m "feat(learning): add learning event domain entity"
```

---

### Task 2: Persistencia — migración, modelo, repositorio

**Files:**
- Create: `modules/Learning/Domain/Repositories/LearningEventRepository.php`
- Create: `modules/Learning/Infrastructure/Persistence/Migrations/2026_08_16_000001_create_learning_events_table.php`
- Create: `modules/Learning/Infrastructure/Persistence/Eloquent/Models/LearningEventModel.php`
- Create: `modules/Learning/Infrastructure/Persistence/Eloquent/Repositories/EloquentLearningEventRepository.php`
- Test: `modules/Learning/Tests/Integration/EloquentLearningEventRepositoryTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Learning\Domain\Entities\LearningEvent;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Learning\Domain\ValueObjects\LearningEventId;
use Modules\Learning\Domain\ValueObjects\LearningVerb;

uses(RefreshDatabase::class);

function persistedEnrollmentForLearningEvents(): Enrollment
{
    $course = createDraftCourseForPublishing('LRN-'.strtoupper((string) Str::random(4)));
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

it('registra y recupera eventos de aprendizaje ordenados del mas reciente al mas antiguo', function (): void {
    $enrollment = persistedEnrollmentForLearningEvents();
    $repository = app(LearningEventRepository::class);

    $repository->record(LearningEvent::create(
        id: LearningEventId::fromString((string) Str::uuid()),
        enrollmentId: $enrollment->id()->value(),
        userId: $enrollment->userId(),
        courseId: $enrollment->courseId()->value(),
        verb: LearningVerb::LessonCompleted,
        subjectId: (string) Str::uuid(),
        occurredAt: new DateTimeImmutable('2026-08-16T09:00:00+00:00'),
        evidence: ['time_spent_minutes' => 5],
    ));
    $repository->record(LearningEvent::create(
        id: LearningEventId::fromString((string) Str::uuid()),
        enrollmentId: $enrollment->id()->value(),
        userId: $enrollment->userId(),
        courseId: $enrollment->courseId()->value(),
        verb: LearningVerb::ExamAttemptSubmitted,
        subjectId: (string) Str::uuid(),
        occurredAt: new DateTimeImmutable('2026-08-16T10:00:00+00:00'),
        evidence: ['score' => 8, 'total_points' => 10, 'percentage' => 80, 'passed' => true],
    ));

    $events = $repository->findByEnrollmentId($enrollment->id()->value());

    expect($events)->toHaveCount(2)
        ->and($events[0]->verb())->toBe(LearningVerb::ExamAttemptSubmitted)
        ->and($events[0]->evidence())->toBe(['score' => 8, 'total_points' => 10, 'percentage' => 80, 'passed' => true])
        ->and($events[1]->verb())->toBe(LearningVerb::LessonCompleted);
});

it('no devuelve eventos de otro enrollment', function (): void {
    $enrollment = persistedEnrollmentForLearningEvents();
    $otherEnrollment = persistedEnrollmentForLearningEvents();
    $repository = app(LearningEventRepository::class);

    $repository->record(LearningEvent::create(
        id: LearningEventId::fromString((string) Str::uuid()),
        enrollmentId: $otherEnrollment->id()->value(),
        userId: $otherEnrollment->userId(),
        courseId: $otherEnrollment->courseId()->value(),
        verb: LearningVerb::LessonCompleted,
        subjectId: (string) Str::uuid(),
        occurredAt: new DateTimeImmutable('now'),
        evidence: [],
    ));

    expect($repository->findByEnrollmentId($enrollment->id()->value()))->toBeEmpty();
});
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Learning/Tests/Integration/EloquentLearningEventRepositoryTest.php`

Expected: FAIL — `LearningEventRepository` no está bindeado en el contenedor todavía (no hay provider, ver Task 3) y la tabla `learning_events` no existe.

**Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Learning\Domain\Repositories;

use Modules\Learning\Domain\Entities\LearningEvent;

interface LearningEventRepository
{
    public function record(LearningEvent $event): void;

    /** @return list<LearningEvent> */
    public function findByEnrollmentId(string $enrollmentId): array;
}
```

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
        Schema::create('learning_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('enrollment_id');
            $table->foreign('enrollment_id')
                ->references('id')
                ->on('academic_enrollments')
                ->cascadeOnDelete();

            $table->uuid('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->uuid('course_id');
            $table->foreign('course_id')
                ->references('id')
                ->on('academic_courses')
                ->cascadeOnDelete();

            $table->string('verb', 60);
            $table->string('subject_id');
            $table->jsonb('evidence');
            $table->timestampTz('occurred_at');

            $table->timestampsTz();

            $table->index(['enrollment_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_events');
    }
};
```

Nota: guarda este archivo con el nombre exacto `2026_08_16_000001_create_learning_events_table.php` (la fecha en el nombre es la que usa Laravel para el orden de ejecución de migraciones).

```php
<?php

declare(strict_types=1);

namespace Modules\Learning\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $enrollment_id
 * @property string $user_id
 * @property string $course_id
 * @property string $verb
 * @property string $subject_id
 * @property array<string, mixed> $evidence
 * @property Carbon $occurred_at
 */
final class LearningEventModel extends Model
{
    use HasUuids;

    protected $table = 'learning_events';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'evidence' => 'array',
        'occurred_at' => 'datetime',
    ];
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Learning\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Learning\Domain\Entities\LearningEvent;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Learning\Domain\ValueObjects\LearningEventId;
use Modules\Learning\Domain\ValueObjects\LearningVerb;
use Modules\Learning\Infrastructure\Persistence\Eloquent\Models\LearningEventModel;

final class EloquentLearningEventRepository implements LearningEventRepository
{
    public function record(LearningEvent $event): void
    {
        LearningEventModel::query()->create([
            'id' => $event->id()->value(),
            'enrollment_id' => $event->enrollmentId(),
            'user_id' => $event->userId(),
            'course_id' => $event->courseId(),
            'verb' => $event->verb()->value,
            'subject_id' => $event->subjectId(),
            'evidence' => $event->evidence(),
            'occurred_at' => $event->occurredAt(),
        ]);
    }

    /** @return list<LearningEvent> */
    public function findByEnrollmentId(string $enrollmentId): array
    {
        return LearningEventModel::query()
            ->where('enrollment_id', $enrollmentId)
            ->orderByDesc('occurred_at')
            ->get()
            ->map(fn (LearningEventModel $model): LearningEvent => LearningEvent::create(
                id: LearningEventId::fromString($model->id),
                enrollmentId: $model->enrollment_id,
                userId: $model->user_id,
                courseId: $model->course_id,
                verb: LearningVerb::from($model->verb),
                subjectId: $model->subject_id,
                occurredAt: DateTimeImmutable::createFromInterface($model->occurred_at),
                evidence: $model->evidence,
            ))
            ->all();
    }
}
```

**Step 4: Bindear temporalmente para poder correr el test**

Este test necesita que `LearningEventRepository` esté bindeado en el contenedor. El binding definitivo se agrega en el `LearningServiceProvider` de la Task 3 — como ese provider todavía no existe, este test seguirá fallando hasta completar la Task 3. **No** crees un provider parcial aquí: continúa a la Task 3, que crea el provider completo, y vuelve a correr este test al final de esa tarea (Step 5 de la Task 3 lo confirma).

**Step 5: Commit**

```bash
git add modules/Learning/Domain/Repositories/LearningEventRepository.php modules/Learning/Infrastructure/Persistence/Migrations/2026_08_16_000001_create_learning_events_table.php modules/Learning/Infrastructure/Persistence/Eloquent/Models/LearningEventModel.php modules/Learning/Infrastructure/Persistence/Eloquent/Repositories/EloquentLearningEventRepository.php modules/Learning/Tests/Integration/EloquentLearningEventRepositoryTest.php
git commit -m "feat(learning): add learning event persistence"
```

---

### Task 3: Módulo — `LearningEventRecorder`, provider, registro en `bootstrap/providers.php`

**Files:**
- Create: `modules/Learning/Application/DTO/LearningEventEntry.php`
- Create: `modules/Learning/Application/Services/LearningEventRecorder.php`
- Create: `modules/Learning/Infrastructure/Services/DefaultLearningEventRecorder.php`
- Create: `modules/Learning/Infrastructure/Providers/LearningServiceProvider.php`
- Modify: `bootstrap/providers.php`
- Test: `modules/Learning/Tests/Integration/LearningServiceProviderTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Modules\Learning\Application\Services\LearningEventRecorder;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Learning\Infrastructure\Persistence\Eloquent\Repositories\EloquentLearningEventRepository;
use Modules\Learning\Infrastructure\Services\DefaultLearningEventRecorder;

it('registra el repositorio de eventos de aprendizaje en el contenedor', function (): void {
    expect(app(LearningEventRepository::class))->toBeInstanceOf(EloquentLearningEventRepository::class);
});

it('registra el recorder de eventos de aprendizaje en el contenedor', function (): void {
    expect(app(LearningEventRecorder::class))->toBeInstanceOf(DefaultLearningEventRecorder::class);
});
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Learning/Tests/Integration/LearningServiceProviderTest.php`

Expected: FAIL — ninguna de las clases existe todavía.

**Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Learning\Application\DTO;

use Modules\Learning\Domain\ValueObjects\LearningVerb;

final readonly class LearningEventEntry
{
    /** @param array<string, mixed> $evidence */
    public function __construct(
        public string $enrollmentId,
        public string $userId,
        public string $courseId,
        public LearningVerb $verb,
        public string $subjectId,
        public array $evidence,
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Learning\Application\Services;

use Modules\Learning\Application\DTO\LearningEventEntry;

interface LearningEventRecorder
{
    public function record(LearningEventEntry $entry): void;
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Learning\Infrastructure\Services;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Learning\Application\DTO\LearningEventEntry;
use Modules\Learning\Application\Services\LearningEventRecorder;
use Modules\Learning\Domain\Entities\LearningEvent;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Learning\Domain\ValueObjects\LearningEventId;

final readonly class DefaultLearningEventRecorder implements LearningEventRecorder
{
    public function __construct(private LearningEventRepository $events) {}

    public function record(LearningEventEntry $entry): void
    {
        $this->events->record(LearningEvent::create(
            id: LearningEventId::fromString((string) Str::uuid()),
            enrollmentId: $entry->enrollmentId,
            userId: $entry->userId,
            courseId: $entry->courseId,
            verb: $entry->verb,
            subjectId: $entry->subjectId,
            occurredAt: new DateTimeImmutable('now'),
            evidence: $entry->evidence,
        ));
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Learning\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Learning\Application\Services\LearningEventRecorder;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Learning\Infrastructure\Persistence\Eloquent\Repositories\EloquentLearningEventRepository;
use Modules\Learning\Infrastructure\Services\DefaultLearningEventRecorder;

final class LearningServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LearningEventRepository::class, EloquentLearningEventRepository::class);
        $this->app->bind(LearningEventRecorder::class, DefaultLearningEventRecorder::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
```

En `bootstrap/providers.php`, agrega `LearningServiceProvider::class` a la lista (junto al resto de módulos, después de `AcademicServiceProvider::class`):

```php
use Modules\Learning\Infrastructure\Providers\LearningServiceProvider;
```

```php
    AcademicServiceProvider::class,
    LearningServiceProvider::class,
```

**Step 4: Run tests to verify they pass.** Expected: PASS — este comando corre tanto el test de esta tarea como el de la Task 2 (que dependía de este binding):

```bash
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Learning/Tests/Integration/LearningServiceProviderTest.php modules/Learning/Tests/Integration/EloquentLearningEventRepositoryTest.php
```

Expected: PASS (4 tests en total).

**Step 5: Commit**

```bash
git add modules/Learning/Application/DTO/LearningEventEntry.php modules/Learning/Application/Services/LearningEventRecorder.php modules/Learning/Infrastructure/Services/DefaultLearningEventRecorder.php modules/Learning/Infrastructure/Providers/LearningServiceProvider.php modules/Learning/Tests/Integration/LearningServiceProviderTest.php bootstrap/providers.php
git commit -m "feat(learning): register learning module provider"
```

---

### Task 4: Consulta — `GetEnrollmentLearningEventsQuery` y su handler

**Files:**
- Create: `modules/Learning/Application/Queries/GetEnrollmentLearningEventsQuery.php`
- Create: `modules/Learning/Application/Responses/LearningEventResponse.php`
- Create: `modules/Learning/Application/UseCases/GetEnrollmentLearningEventsHandler.php`
- Modify: `modules/Learning/Infrastructure/Providers/LearningServiceProvider.php`
- Test: `modules/Learning/Tests/Unit/Application/GetEnrollmentLearningEventsHandlerTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Learning\Application\Queries\GetEnrollmentLearningEventsQuery;
use Modules\Learning\Application\Responses\LearningEventResponse;
use Modules\Learning\Application\UseCases\GetEnrollmentLearningEventsHandler;
use Modules\Learning\Domain\Entities\LearningEvent;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Learning\Domain\ValueObjects\LearningEventId;
use Modules\Learning\Domain\ValueObjects\LearningVerb;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function getEnrollmentLearningEventsHandler(): GetEnrollmentLearningEventsHandler
{
    return new GetEnrollmentLearningEventsHandler(
        app(EnrollmentRepository::class),
        app(LearningEventRepository::class),
    );
}

function persistedLearningEventsUserId(): string
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

function activeEnrollmentForLearningEvents(): Enrollment
{
    $course = createDraftCourseForPublishing('LRN-Q-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: persistedLearningEventsUserId(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

it('devuelve los eventos de aprendizaje al dueno del enrollment', function (): void {
    $enrollment = activeEnrollmentForLearningEvents();
    app(LearningEventRepository::class)->record(LearningEvent::create(
        id: LearningEventId::fromString((string) Str::uuid()),
        enrollmentId: $enrollment->id()->value(),
        userId: $enrollment->userId(),
        courseId: $enrollment->courseId()->value(),
        verb: LearningVerb::LessonCompleted,
        subjectId: (string) Str::uuid(),
        occurredAt: new DateTimeImmutable('now'),
        evidence: ['time_spent_minutes' => 3],
    ));

    $response = getEnrollmentLearningEventsHandler()->handle(new GetEnrollmentLearningEventsQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: $enrollment->userId(),
        canViewOthers: false,
    ));

    expect($response)->toBeInstanceOf(LearningEventResponse::class)
        ->and($response->enrollmentId)->toBe($enrollment->id()->value())
        ->and($response->events)->toHaveCount(1)
        ->and($response->events[0]['verb'])->toBe('lesson_completed');
});

it('rechaza consultar los eventos de un enrollment ajeno sin permiso ampliado', function (): void {
    $enrollment = activeEnrollmentForLearningEvents();

    expect(fn () => getEnrollmentLearningEventsHandler()->handle(new GetEnrollmentLearningEventsQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: persistedLearningEventsUserId(),
        canViewOthers: false,
    )))->toThrow(EnrollmentNotFound::class);
});

it('permite consultar los eventos de un enrollment ajeno con permiso ampliado', function (): void {
    $enrollment = activeEnrollmentForLearningEvents();

    $response = getEnrollmentLearningEventsHandler()->handle(new GetEnrollmentLearningEventsQuery(
        enrollmentId: $enrollment->id()->value(),
        userId: persistedLearningEventsUserId(),
        canViewOthers: true,
    ));

    expect($response->enrollmentId)->toBe($enrollment->id()->value());
});

it('rechaza consultar los eventos de un enrollment inexistente', function (): void {
    expect(fn () => getEnrollmentLearningEventsHandler()->handle(new GetEnrollmentLearningEventsQuery(
        enrollmentId: (string) Str::uuid(),
        userId: (string) Str::uuid(),
        canViewOthers: true,
    )))->toThrow(EnrollmentNotFound::class);
});
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Learning/Tests/Unit/Application/GetEnrollmentLearningEventsHandlerTest.php`

Expected: FAIL — las 3 clases no existen todavía.

**Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Learning\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetEnrollmentLearningEventsQuery implements Query
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

namespace Modules\Learning\Application\Responses;

final readonly class LearningEventResponse
{
    /**
     * @param  list<array{verb: string, subject_id: string, occurred_at: string, evidence: array<string, mixed>}>  $events
     */
    public function __construct(
        public string $enrollmentId,
        public array $events,
    ) {}

    /**
     * @return array{enrollment_id: string, events: list<array{verb: string, subject_id: string, occurred_at: string, evidence: array<string, mixed>}>}
     */
    public function toArray(): array
    {
        return [
            'enrollment_id' => $this->enrollmentId,
            'events' => $this->events,
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Learning\Application\UseCases;

use DateTimeInterface;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Learning\Application\Queries\GetEnrollmentLearningEventsQuery;
use Modules\Learning\Application\Responses\LearningEventResponse;
use Modules\Learning\Domain\Entities\LearningEvent;
use Modules\Learning\Domain\Repositories\LearningEventRepository;

final readonly class GetEnrollmentLearningEventsHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private LearningEventRepository $events,
    ) {}

    public function handle(GetEnrollmentLearningEventsQuery $query): LearningEventResponse
    {
        $enrollment = $this->enrollments->findById(EnrollmentId::fromString($query->enrollmentId));
        if ($enrollment === null) {
            throw EnrollmentNotFound::withId($query->enrollmentId);
        }

        if ($enrollment->userId() !== $query->userId && ! $query->canViewOthers) {
            throw EnrollmentNotFound::withId($query->enrollmentId);
        }

        $events = $this->events->findByEnrollmentId($enrollment->id()->value());

        return new LearningEventResponse(
            enrollmentId: $enrollment->id()->value(),
            events: array_map(
                static fn (LearningEvent $event): array => [
                    'verb' => $event->verb()->value,
                    'subject_id' => $event->subjectId(),
                    'occurred_at' => $event->occurredAt()->format(DateTimeInterface::ATOM),
                    'evidence' => $event->evidence(),
                ],
                $events,
            ),
        );
    }
}
```

En `LearningServiceProvider.php`, agrega el registro CQRS. El método `boot()` ahora necesita el `MessageHandlerRegistry`:

```php
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Learning\Application\Queries\GetEnrollmentLearningEventsQuery;
use Modules\Learning\Application\UseCases\GetEnrollmentLearningEventsHandler;
```

```php
    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(
            GetEnrollmentLearningEventsQuery::class,
            GetEnrollmentLearningEventsHandler::class,
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
```

**Step 4: Run test to verify it passes.** Expected: PASS (4 tests).

**Step 5: Commit**

```bash
git add modules/Learning/Application/Queries/GetEnrollmentLearningEventsQuery.php modules/Learning/Application/Responses/LearningEventResponse.php modules/Learning/Application/UseCases/GetEnrollmentLearningEventsHandler.php modules/Learning/Infrastructure/Providers/LearningServiceProvider.php modules/Learning/Tests/Unit/Application/GetEnrollmentLearningEventsHandlerTest.php
git commit -m "feat(learning): add get enrollment learning events handler"
```

---

### Task 5: API HTTP de eventos de aprendizaje

**Files:**
- Create: `modules/Learning/Presentation/Http/Controllers/LearningEventController.php`
- Create: `modules/Learning/Presentation/Routes/api.php`
- Modify: `modules/Learning/Infrastructure/Providers/LearningServiceProvider.php`
- Test: `modules/Learning/Tests/Feature/LearningEventTest.php`

**Step 1: Write the failing feature tests**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Learning\Domain\Entities\LearningEvent;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Learning\Domain\ValueObjects\LearningEventId;
use Modules\Learning\Domain\ValueObjects\LearningVerb;
use Tests\TestCase;

uses(RefreshDatabase::class);

function activeEnrollmentForLearningEventsFeature(?string $userId = null): Enrollment
{
    $course = createDraftCourseForPublishing('LRN-F-'.strtoupper((string) Str::random(4)));
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

it('consulta los eventos de aprendizaje propios', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $enrollment = activeEnrollmentForLearningEventsFeature($userId);
    app(LearningEventRepository::class)->record(LearningEvent::create(
        id: LearningEventId::fromString((string) Str::uuid()),
        enrollmentId: $enrollment->id()->value(),
        userId: $userId,
        courseId: $enrollment->courseId()->value(),
        verb: LearningVerb::LessonCompleted,
        subjectId: (string) Str::uuid(),
        occurredAt: new DateTimeImmutable('now'),
        evidence: [],
    ));

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/learning-events")
        ->assertOk()
        ->assertJsonPath('data.enrollment_id', $enrollment->id()->value())
        ->assertJsonPath('data.events.0.verb', 'lesson_completed');
});

it('rechaza consultar eventos de aprendizaje ajenos sin permiso ampliado', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $enrollment = activeEnrollmentForLearningEventsFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/learning-events")
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});

it('permite consultar eventos de aprendizaje ajenos con enrollments.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $enrollment = activeEnrollmentForLearningEventsFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/learning-events")
        ->assertOk()
        ->assertJsonPath('data.enrollment_id', $enrollment->id()->value())
        ->assertJsonPath('data.events', []);
});

it('responde 404 al consultar eventos de aprendizaje de un enrollment inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/academic/enrollments/'.Str::uuid().'/learning-events')
        ->assertNotFound()
        ->assertJsonPath('code', 'ENROLLMENT_NOT_FOUND');
});
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Learning/Tests/Feature/LearningEventTest.php`

Expected: FAIL — la ruta no existe todavía (404 genérico de Laravel, no el `ENROLLMENT_NOT_FOUND` esperado).

**Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Learning\Presentation\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Learning\Application\Queries\GetEnrollmentLearningEventsQuery;
use Modules\Learning\Application\Responses\LearningEventResponse;

final class LearningEventController
{
    public function index(
        string $enrollmentId,
        Request $request,
        QueryBus $queryBus,
        PermissionChecker $permissionChecker,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetEnrollmentLearningEventsQuery(
            enrollmentId: $enrollmentId,
            userId: (string) $user->getAuthIdentifier(),
            canViewOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ViewEnrollments),
        ));
        assert($result instanceof LearningEventResponse);

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

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Learning\Presentation\Http\Controllers\LearningEventController;

Route::prefix('api/v1/academic')
    ->name('api.v1.academic.')
    ->group(function (): void {
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/enrollments/{enrollmentId}/learning-events', [LearningEventController::class, 'index'])
                ->whereUuid('enrollmentId')
                ->name('enrollments.learning-events.index');
        });
    });
```

En `LearningServiceProvider.php`, agrega la carga de rutas en `boot()`:

```php
    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(
            GetEnrollmentLearningEventsQuery::class,
            GetEnrollmentLearningEventsHandler::class,
        );

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
```

**Step 4: Run test to verify it passes.** Expected: PASS (4 tests).

**Step 5: Commit**

```bash
git add modules/Learning/Presentation/Http/Controllers/LearningEventController.php modules/Learning/Presentation/Routes/api.php modules/Learning/Infrastructure/Providers/LearningServiceProvider.php modules/Learning/Tests/Feature/LearningEventTest.php
git commit -m "feat(learning): add enrollment learning events http api"
```

---

### Task 6: `CompleteLessonHandler` registra el evento de leccion completada

**Files:**
- Modify: `modules/Academic/Application/UseCases/CompleteLessonHandler.php`
- Modify: `modules/Academic/Tests/Unit/Application/CompleteLessonHandlerTest.php`

**Step 1: Write the failing test**

Agrega al archivo existente, junto a sus `use` correspondientes:

```php
use Modules\Learning\Application\DTO\LearningEventEntry;
use Modules\Learning\Application\Services\LearningEventRecorder;
use Modules\Learning\Domain\ValueObjects\LearningVerb;
```

```php
final class SpyLearningEventRecorder implements LearningEventRecorder
{
    /** @var list<LearningEventEntry> */
    public array $recorded = [];

    public function record(LearningEventEntry $entry): void
    {
        $this->recorded[] = $entry;
    }
}
```

Actualiza el helper `completeLessonHandler()` para aceptar (y devolver) el spy, de modo que los tests puedan inspeccionar lo registrado:

```php
function completeLessonHandler(?SpyLearningEventRecorder $recorder = null): CompleteLessonHandler
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
        $recorder ?? new SpyLearningEventRecorder,
    );
}
```

Agrega el nuevo caso de test al final del archivo:

```php
it('registra un evento de aprendizaje al completar una leccion', function (): void {
    $enrollment = activeEnrollmentForLessonCompletion();
    $course = app(CourseRepository::class)->findById($enrollment->courseId());
    $lessonId = (new CourseLessonCatalog(app(UnitContentRepository::class)))->lessonIdsFor($course)[0];
    $recorder = new SpyLearningEventRecorder;

    completeLessonHandler($recorder)->handle(new CompleteLessonCommand(
        enrollmentId: $enrollment->id()->value(),
        lessonId: $lessonId,
        userId: $enrollment->userId(),
        timeSpentMinutes: 9,
    ));

    expect($recorder->recorded)->toHaveCount(1)
        ->and($recorder->recorded[0]->enrollmentId)->toBe($enrollment->id()->value())
        ->and($recorder->recorded[0]->userId)->toBe($enrollment->userId())
        ->and($recorder->recorded[0]->courseId)->toBe($enrollment->courseId()->value())
        ->and($recorder->recorded[0]->verb)->toBe(LearningVerb::LessonCompleted)
        ->and($recorder->recorded[0]->subjectId)->toBe($lessonId)
        ->and($recorder->recorded[0]->evidence)->toBe(['time_spent_minutes' => 9]);
});
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Application/CompleteLessonHandlerTest.php`

Expected: FAIL — el constructor de `CompleteLessonHandler` todavía no acepta el nuevo argumento.

**Step 3: Write minimal implementation**

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
use Modules\Learning\Application\DTO\LearningEventEntry;
use Modules\Learning\Application\Services\LearningEventRecorder;
use Modules\Learning\Domain\ValueObjects\LearningVerb;

final readonly class CompleteLessonHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private EnrollmentProgressRepository $progressRepository,
        private CourseRepository $courses,
        private CourseLessonCatalog $lessonCatalog,
        private CourseCurriculumUnlockCalculator $unlockCalculator,
        private EnrollmentProgressCalculator $calculator,
        private LearningEventRecorder $learningEvents,
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

        $this->learningEvents->record(new LearningEventEntry(
            enrollmentId: $enrollment->id()->value(),
            userId: $enrollment->userId(),
            courseId: $enrollment->courseId()->value(),
            verb: LearningVerb::LessonCompleted,
            subjectId: $lessonId->value(),
            evidence: ['time_spent_minutes' => $command->timeSpentMinutes],
        ));

        return $this->calculator->calculate($enrollment, $progress);
    }
}
```

**Step 4: Run test to verify it passes.** Expected: PASS (6 tests: los 5 ya existentes de ENG-036/037 + el nuevo).

**Step 5: Commit**

```bash
git add modules/Academic/Application/UseCases/CompleteLessonHandler.php modules/Academic/Tests/Unit/Application/CompleteLessonHandlerTest.php
git commit -m "feat(academic): record learning event on lesson completion"
```

---

### Task 7: `SubmitExamAttemptHandler` registra el evento de examen enviado

**Files:**
- Modify: `modules/Academic/Application/UseCases/SubmitExamAttemptHandler.php`
- Modify: `modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php`

**Contexto importante:** `ExamAttempt` está anclado a `courseId`, no a `enrollmentId` directamente. Este handler resuelve el enrollment del alumno para ese curso vía `EnrollmentRepository::findActiveOrPendingFor()` (ya existente desde ENG-035). Los dos colaboradores nuevos (`EnrollmentRepository`, `LearningEventRecorder`) se agregan como **parámetros opcionales con default `null`**, siguiendo la convención ya establecida en este mismo archivo para `$grader`/`$exams`/`$recommendations` — así ningún `new SubmitExamAttemptHandler(...)` existente en el test se rompe; el registro del evento simplemente no ocurre si no se proveen.

**Step 1: Write the failing test**

Agrega al archivo existente, junto a sus `use` correspondientes:

```php
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Learning\Application\DTO\LearningEventEntry;
use Modules\Learning\Application\Services\LearningEventRecorder;
use Modules\Learning\Domain\ValueObjects\LearningVerb;
```

```php
final class SpyLearningEventRecorderForAttempts implements LearningEventRecorder
{
    /** @var list<LearningEventEntry> */
    public array $recorded = [];

    public function record(LearningEventEntry $entry): void
    {
        $this->recorded[] = $entry;
    }
}
```

Agrega el nuevo caso de test al final del archivo (reutiliza `persistedAttemptExam()` ya existente en este archivo, que persiste un curso real vía `createDraftCourseForPublishing()` y devuelve `[examId, questionIds]`):

```php
it('registra un evento de aprendizaje al enviar un intento calificado', function (): void {
    [$examId, $questionIds] = persistedAttemptExam();
    $exam = app(ExamRepository::class)->findById(\Modules\Academic\Domain\ValueObjects\ExamId::fromString($examId));
    $userId = (string) Str::uuid();

    $enrollment = \Modules\Academic\Domain\Aggregates\Enrollment::create(
        id: \Modules\Academic\Domain\ValueObjects\EnrollmentId::fromString((string) Str::uuid()),
        courseId: $exam->courseId(),
        userId: $userId,
        status: \Modules\Academic\Domain\Enums\EnrollmentStatus::Active,
        source: \Modules\Academic\Domain\Enums\EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $started = $start->handle(new StartExamAttemptCommand($examId, $userId));

    $answer = new AnswerAttemptQuestionHandler($repository);
    $answer->handle(new AnswerAttemptQuestionCommand(
        $started->id,
        $userId,
        1,
        new SingleChoiceResponse(QuestionOptionId::fromString($questionIds[0])),
    ));

    $recorder = new SpyLearningEventRecorderForAttempts;
    $submit = new SubmitExamAttemptHandler(
        $repository,
        null,
        app(ExamRepository::class),
        null,
        app(EnrollmentRepository::class),
        $recorder,
    );
    $submit->handle(new SubmitExamAttemptCommand($started->id, $userId));

    expect($recorder->recorded)->toHaveCount(1)
        ->and($recorder->recorded[0]->enrollmentId)->toBe($enrollment->id()->value())
        ->and($recorder->recorded[0]->userId)->toBe($userId)
        ->and($recorder->recorded[0]->courseId)->toBe($exam->courseId()->value())
        ->and($recorder->recorded[0]->verb)->toBe(LearningVerb::ExamAttemptSubmitted)
        ->and($recorder->recorded[0]->subjectId)->toBe($started->id)
        ->and($recorder->recorded[0]->evidence)->toHaveKeys(['score', 'total_points', 'percentage', 'passed']);
});

it('no falla ni registra un evento si no hay enrollment resoluble para el curso del examen', function (): void {
    [$examId, $questionIds] = persistedAttemptExam();
    $userId = (string) Str::uuid();

    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $started = $start->handle(new StartExamAttemptCommand($examId, $userId));

    $answer = new AnswerAttemptQuestionHandler($repository);
    $answer->handle(new AnswerAttemptQuestionCommand(
        $started->id,
        $userId,
        1,
        new SingleChoiceResponse(QuestionOptionId::fromString($questionIds[0])),
    ));

    $recorder = new SpyLearningEventRecorderForAttempts;
    $submit = new SubmitExamAttemptHandler(
        $repository,
        null,
        app(ExamRepository::class),
        null,
        app(EnrollmentRepository::class),
        $recorder,
    );
    $response = $submit->handle(new SubmitExamAttemptCommand($started->id, $userId));

    expect($response)->toBeInstanceOf(ExamAttemptResponse::class)
        ->and($recorder->recorded)->toBeEmpty();
});
```

**Step 2: Run test to verify it fails**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php`

Expected: los tests ya existentes en el archivo pasan igual (constructor sigue siendo compatible), los 2 nuevos FALLAN porque `SubmitExamAttemptHandler` todavía no acepta los argumentos 5 y 6.

**Step 3: Write minimal implementation**

Reemplaza el contenido completo de `SubmitExamAttemptHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Modules\Academic\Application\Commands\SubmitExamAttemptCommand;
use Modules\Academic\Application\Exceptions\ExamAttemptAlreadySubmitted;
use Modules\Academic\Application\Exceptions\ExamAttemptNotFound;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Application\Services\TheoryStudyRecommendationService;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamKind;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Services\ExamAttemptGrader;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\GradingPolicy;
use Modules\Learning\Application\DTO\LearningEventEntry;
use Modules\Learning\Application\Services\LearningEventRecorder;
use Modules\Learning\Domain\ValueObjects\LearningVerb;

final readonly class SubmitExamAttemptHandler
{
    public function __construct(
        private ExamAttemptRepository $attempts,
        private ?ExamAttemptGrader $grader = null,
        private ?ExamRepository $exams = null,
        private ?TheoryStudyRecommendationService $recommendations = null,
        private ?EnrollmentRepository $enrollments = null,
        private ?LearningEventRecorder $learningEvents = null,
    ) {}

    public function handle(SubmitExamAttemptCommand $command): ExamAttemptResponse
    {
        $attempt = $this->ownedAttempt($command->attemptId, $command->userId);
        if ($attempt->status() !== ExamAttemptStatus::InProgress) {
            throw ExamAttemptAlreadySubmitted::create();
        }

        $submittedAt = new DateTimeImmutable('now');

        if ($attempt->hasTimedOutAt($submittedAt)) {
            $attempt->submit($submittedAt);
            $this->attempts->save($attempt);

            return ExamAttemptResponse::fromAttempt($attempt, ExamAttemptResponse::questionMapper(false), false);
        }

        $exam = $this->examForAttempt($attempt);

        $gradingResult = ($this->grader ?? new ExamAttemptGrader)->grade(
            $attempt,
            $this->gradingPolicyFor($exam),
        );

        $attempt->submit($submittedAt, $gradingResult);

        $this->attempts->save($attempt);

        $this->recordLearningEvent($attempt, $exam);

        $studyRecommendations = $exam === null
            ? null
            : ($this->recommendations ?? new TheoryStudyRecommendationService)->build($attempt, $exam);

        return ExamAttemptResponse::fromAttempt(
            $attempt,
            ExamAttemptResponse::questionMapper(true),
            true,
            $studyRecommendations,
        );
    }

    private function ownedAttempt(string $attemptId, string $userId): ExamAttempt
    {
        $attempt = $this->attempts->findById(ExamAttemptId::fromString($attemptId));
        if ($attempt === null || $attempt->userId() !== $userId) {
            throw ExamAttemptNotFound::withId($attemptId);
        }

        return $attempt;
    }

    private function examForAttempt(ExamAttempt $attempt): ?Exam
    {
        return $this->exams?->findById($attempt->examId());
    }

    private function gradingPolicyFor(?Exam $exam): GradingPolicy
    {
        if ($exam !== null && $exam->kind() === ExamKind::Theory) {
            return new GradingPolicy(
                allowPartialCredit: $exam->allowPartialCredit(),
                applyPenalties: $exam->applyPenalties(),
            );
        }

        return new GradingPolicy(allowPartialCredit: true, applyPenalties: true);
    }

    private function recordLearningEvent(ExamAttempt $attempt, ?Exam $exam): void
    {
        if ($this->enrollments === null || $this->learningEvents === null || $exam === null) {
            return;
        }

        $enrollment = $this->enrollments->findActiveOrPendingFor($exam->courseId(), $attempt->userId());
        if ($enrollment === null) {
            return;
        }

        $this->learningEvents->record(new LearningEventEntry(
            enrollmentId: $enrollment->id()->value(),
            userId: $attempt->userId(),
            courseId: $exam->courseId()->value(),
            verb: LearningVerb::ExamAttemptSubmitted,
            subjectId: $attempt->id()->value(),
            evidence: [
                'score' => $attempt->score(),
                'total_points' => $attempt->totalPoints(),
                'percentage' => $attempt->percentage(),
                'passed' => $attempt->passed(),
            ],
        ));
    }
}
```

**Step 4: Run test to verify it passes.** Expected: PASS (todos los tests existentes del archivo + los 2 nuevos).

**Step 5: Commit**

```bash
git add modules/Academic/Application/UseCases/SubmitExamAttemptHandler.php modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php
git commit -m "feat(academic): record learning event on exam attempt submission"
```

---

### Task 8: Verificación completa

**Files:**
- Verify: todos los archivos creados/modificados en las Tasks 1-7.

**Step 1: Ejecutar la suite completa de ENG-038**

Run:
```bash
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Learning modules/Academic/Tests/Unit/Application/CompleteLessonHandlerTest.php modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php
```

Expected: PASS en todos.

**Step 2: Pint**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php vendor/bin/pint --test modules/Learning modules/Academic/Application/UseCases/CompleteLessonHandler.php modules/Academic/Application/UseCases/SubmitExamAttemptHandler.php modules/Academic/Tests/Unit/Application/CompleteLessonHandlerTest.php modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php bootstrap/providers.php`

Expected: sin issues nuevos atribuibles a estos archivos (si Pint modifica algo, re-ejecutar Step 1).

**Step 3: PHPStan**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php vendor/bin/phpstan analyse --no-progress --memory-limit=1G modules/Learning modules/Academic/Application/UseCases/CompleteLessonHandler.php modules/Academic/Application/UseCases/SubmitExamAttemptHandler.php`

Expected: sin errores.

**Step 4: Verificación de rutas**

Run: `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan route:list --path=academic/enrollments`

Expected: se ve la nueva ruta `enrollments/{enrollmentId}/learning-events` junto a las 11 de ENG-035/036/037.

**Step 5: Revisar el diff**

Run: `git log --oneline` (confirmar solo commits de ENG-038 desde el design doc) y `git status --short` (confirmar que no quedó nada de ENG-038 sin commitear ni nada ajeno tocado — el trabajo ya existente de ENG-032/033/034/035 sin commitear, si lo hay, no se toca en esta historia).

**Step 6: Actualizar roadmap y ENG-LOG**

Actualiza `docs/roadmap/ENG-000-roadmap-tecnico-backend.md` (sección ENG-038, estado `Completado` + nota) y agrega la entrada `IMP-038` en `docs/engineering/ENG-LOG.md`, siguiendo el formato de `IMP-037`. Commit aparte:

```bash
git add docs/roadmap/ENG-000-roadmap-tecnico-backend.md docs/engineering/ENG-LOG.md
git commit -m "docs(engineering): close ENG-038 learning record store"
```

---

## Nota de arquitectura: acoplamiento bidireccional entre `Academic` y `Learning`

Este incremento crea dependencias en ambas direcciones entre los dos módulos:

- **Escritura**: `Academic` (`CompleteLessonHandler`, `SubmitExamAttemptHandler`) depende de la abstracción `LearningEventRecorder` de `Learning\Application` — mismo patrón ya usado por `Identity` → `Audit` (`AuditLogger`).
- **Lectura**: `Learning` (`GetEnrollmentLearningEventsHandler`) depende de `EnrollmentRepository` y `EnrollmentNotFound` de `Academic`, porque autorizar "¿puede este usuario ver los eventos de este enrollment?" requiere preguntarle a `Academic` si el enrollment existe y a quién pertenece — es la misma pregunta que ya resuelve `GetEnrollmentProgressHandler`/`GetEnrollmentCurriculumStatusHandler` dentro de `Academic` mismo.

Es un acoplamiento real e intencional dado el alcance aprobado (eventos con autorización por pertenencia al enrollment), no un descuido de capas. Si en una historia futura `Learning` necesita independencia total de `Academic` (por ejemplo, para exponer eventos de otros dominios sin enrollment), valdría la pena revisar esta dirección — pero está fuera de alcance de ENG-038.
