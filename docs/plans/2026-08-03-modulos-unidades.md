# ENG-027 — Módulos y unidades — Plan de implementación

**Historia:** ENG-027

> **For Codex:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Incorporar a cada curso un currículo regional jerárquico, ordenado y transaccional compuesto por módulos y unidades con prerrequisitos hacia elementos anteriores.

**Architecture:** `Course` continúa como raíz del agregado y controla una colección de `CourseModule`, cada uno con `CourseUnit`. La API consulta y reemplaza el currículo completo mediante `QueryBus`/`CommandBus`; el agregado valida la estructura candidata antes de mutar y `EloquentCourseRepository` la sincroniza en una sola transacción, preservando UUID estables.

**Tech Stack:** PHP 8.4, Laravel 12, PostgreSQL/SQLite de pruebas, Eloquent, Pest, Larastan/PHPStan, Pint.

---

## Contexto y convenciones

- Diseño aprobado: `docs/plans/2026-08-03-modulos-unidades-design.md`.
- Worktree: `C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-027-modulos-unidades`.
- Rama: `codex/eng-027-modulos-unidades`.
- Línea base: Pint 314 archivos, PHPStan 238 archivos, 222 pruebas y 730 aserciones.
- Ejecutar PHP/Composer en un contenedor efímero que monte **este worktree exacto**. No usar `docker compose exec`: el contenedor persistente `edudrive-app` monta otro checkout.
- Comando base de Docker para pruebas:

```powershell
docker run --rm --network edudrive_edudrive-network --workdir /var/www/html `
  --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-027-modulos-unidades:/var/www/html" `
  --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" `
  --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" `
  edudrive-app php artisan test <rutas>
```

- Todo archivo PHP nuevo lleva `declare(strict_types=1);`.
- TDD obligatorio: RED observado antes de escribir producción, GREEN focal fresco después y revisión del diff antes de cada commit.
- No agregar endpoints granulares, UI web, lecciones, multimedia, progreso, perfiles nacionales ni versionado.

### Contrato curricular usado en todas las tareas

```json
{
  "modules": [
    {
      "id": "019...",
      "code": "MOD-01",
      "title": "Fundamentos",
      "description": "Bases de seguridad vial.",
      "objectives": "Reconocer riesgos frecuentes.",
      "duration_minutes": 90,
      "position": 1,
      "prerequisite_module_ids": [],
      "units": [
        {
          "id": "019...",
          "code": "UNI-01",
          "title": "Percepción del riesgo",
          "description": "Identificación de peligros.",
          "objectives": null,
          "duration_minutes": 30,
          "position": 1,
          "prerequisite_unit_ids": []
        }
      ]
    }
  ]
}
```

Los códigos se normalizan a mayúsculas y son únicos en su contenedor. Las posiciones empiezan en 1 y son consecutivas. Un prerrequisito siempre debe haber aparecido antes en el recorrido módulo/posición y unidad/posición.

---

### Task 1: Primitivas y entidades curriculares

**Files:**

- Create: `modules/Academic/Domain/ValueObjects/CourseModuleId.php`
- Create: `modules/Academic/Domain/ValueObjects/CourseUnitId.php`
- Create: `modules/Academic/Domain/ValueObjects/CurriculumCode.php`
- Create: `modules/Academic/Domain/Entities/CourseModule.php`
- Create: `modules/Academic/Domain/Entities/CourseUnit.php`
- Create: `modules/Academic/Domain/Exceptions/InvalidCurriculumText.php`
- Create: `modules/Academic/Domain/Exceptions/InvalidCurriculumDuration.php`
- Test: `modules/Academic/Tests/Unit/Domain/ValueObjects/CurriculumCodeTest.php`
- Test: `modules/Academic/Tests/Unit/Domain/Entities/CourseModuleTest.php`
- Test: `modules/Academic/Tests/Unit/Domain/Entities/CourseUnitTest.php`

**Public contracts:**

```php
final readonly class CourseUnit
{
    /** @param list<CourseUnitId> $prerequisiteUnitIds */
    public static function create(
        CourseUnitId $id,
        CurriculumCode $code,
        string $title,
        string $description,
        ?string $objectives,
        ?int $durationMinutes,
        int $position,
        array $prerequisiteUnitIds,
    ): self;
}

final readonly class CourseModule
{
    /**
     * @param list<CourseModuleId> $prerequisiteModuleIds
     * @param list<CourseUnit> $units
     */
    public static function create(
        CourseModuleId $id,
        CurriculumCode $code,
        string $title,
        string $description,
        ?string $objectives,
        ?int $durationMinutes,
        int $position,
        array $prerequisiteModuleIds,
        array $units,
    ): self;
}
```

IDs siguen el patrón de `CourseId`: UUID válido, valor normalizado y `equals()`. `CurriculumCode` reutiliza las reglas de formato de `CourseCode`, con máximo de 60 caracteres. Entidades normalizan espacios exteriores, exigen título (máx. 180), descripción no vacía, objetivos opcionales y duración positiva. La validación cruzada de posiciones, duplicados y prerrequisitos pertenece a `Course`, no a las entidades.

**Step 1: Escribir las pruebas RED**

Cubrir:

- UUID válido/inválido para módulo y unidad.
- Código normalizado, vacío, demasiado largo y caracteres inválidos.
- Construcción completa de módulo/unidad.
- Título o descripción vacíos.
- Duración 0/negativa.
- Objetivos vacíos normalizados a `null`.

**Step 2: Ejecutar RED**

```powershell
docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-027-modulos-unidades:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/ValueObjects/CurriculumCodeTest.php modules/Academic/Tests/Unit/Domain/Entities/CourseModuleTest.php modules/Academic/Tests/Unit/Domain/Entities/CourseUnitTest.php
```

Expected: FAIL porque las clases todavía no existen.

**Step 3: Implementar el mínimo dominio local**

- Excepciones extienden `Modules\Foundation\Domain\Exceptions\DomainException`.
- `InvalidCurriculumText`: HTTP 422, código `INVALID_CURRICULUM_TEXT`.
- `InvalidCurriculumDuration`: HTTP 422, código `INVALID_CURRICULUM_DURATION`.
- Posición debe ser positiva en la entidad; la consecutividad se valida después en el agregado.
- Exponer getters tipados para todos los campos y arrays como `list<>`.

**Step 4: Ejecutar GREEN y análisis focal**

Repetir el comando del Step 2 y después:

```powershell
docker run --rm --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-027-modulos-unidades:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" edudrive-app vendor/bin/phpstan analyse modules/Academic/Domain/Entities modules/Academic/Domain/ValueObjects
```

Expected: PASS y `[OK] No errors`.

**Step 5: Commit**

```bash
git add modules/Academic/Domain modules/Academic/Tests/Unit/Domain
git commit -m "feat(academic): add course curriculum entities"
```

---

### Task 2: Currículo dentro del agregado Course y reglas de publicación

**Files:**

- Modify: `modules/Academic/Domain/Aggregates/Course.php`
- Create: `modules/Academic/Domain/Exceptions/CourseCurriculumCannotBeModified.php`
- Create: `modules/Academic/Domain/Exceptions/CourseCurriculumRequired.php`
- Create: `modules/Academic/Domain/Exceptions/CourseModuleRequiresUnits.php`
- Create: `modules/Academic/Domain/Exceptions/DuplicateCourseModule.php`
- Create: `modules/Academic/Domain/Exceptions/DuplicateCourseUnit.php`
- Create: `modules/Academic/Domain/Exceptions/InvalidCurriculumPosition.php`
- Create: `modules/Academic/Domain/Exceptions/InvalidCurriculumPrerequisite.php`
- Modify: `modules/Academic/Tests/Unit/Domain/Aggregates/CourseTest.php`
- Modify: `tests/Pest.php`
- Modify: `modules/Academic/Tests/Feature/PublishCourseTest.php`
- Modify: `modules/Academic/Tests/Unit/Application/PublishProgramHandlerTest.php`
- Modify: `modules/Academic/Tests/Unit/Application/ReplaceProgramCoursesHandlerTest.php`

**Aggregate contract:**

```php
final class Course
{
    /** @param list<CourseModule> $modules */
    public static function restore(
        CourseId $id,
        CourseCode $code,
        CourseTitle $title,
        ?string $description,
        ?string $objectives,
        ?string $prerequisites,
        ?CourseModality $modality,
        ?int $durationHours,
        CourseStatus $status,
        ?DateTimeImmutable $publishedAt,
        ?DateTimeImmutable $archivedAt,
        array $modules = [],
    ): self;

    /** @param list<CourseModule> $modules */
    public function replaceCurriculum(array $modules): void;

    /** @return list<CourseModule> */
    public function modules(): array;
}
```

`create()` inicia con `modules: []`. `restore()` acepta vacío incluso en un curso ya publicado para compatibilidad. `replaceCurriculum()` solo funciona en `draft` y valida completamente una copia candidata antes de asignarla.

**Invariantes:**

- Posiciones consecutivas desde 1 para módulos y unidades.
- UUID y código de módulo únicos en el curso.
- UUID de unidad único en todo el curso; código de unidad único dentro del módulo.
- Módulo prerrequisito ya visto en módulos anteriores.
- Unidad prerrequisito ya vista en el recorrido curricular anterior.
- Prerrequisitos sin duplicados.
- `publish()` exige al menos un módulo y al menos una unidad por módulo.
- `published` y `archived` rechazan `replaceCurriculum()`.
- Ante cualquier error, `modules()` conserva exactamente la estructura anterior.

**Error codes:**

- `COURSE_CURRICULUM_CANNOT_BE_MODIFIED`
- `COURSE_CURRICULUM_REQUIRED`
- `COURSE_MODULE_REQUIRES_UNITS`
- `DUPLICATE_COURSE_MODULE`
- `DUPLICATE_COURSE_UNIT`
- `INVALID_CURRICULUM_POSITION`
- `INVALID_CURRICULUM_PREREQUISITE`

Todos son 422.

**Step 1: Escribir pruebas RED del agregado**

Agregar builders de módulos/unidades y cubrir reemplazo válido, cada invariante, atomicidad, publicación incompleta, publicación válida, inmutabilidad published/archived y restauración de un published legado vacío.

**Step 2: Ejecutar RED**

```powershell
docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-027-modulos-unidades:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/CourseTest.php
```

Expected: FAIL porque `replaceCurriculum()`/`modules()` y las nuevas reglas no existen.

**Step 3: Implementar las invariantes**

Validar en un método puro `validateCurriculum(array $modules): void`; asignar `$this->modules = $modules` únicamente al final. El orden de guards es lifecycle primero, luego estructura.

**Step 4: Mantener verdes los consumidores existentes de cursos publicados**

Agregar en `tests/Pest.php`:

```php
function addMinimalCurriculum(Course $course): void
{
    // Un módulo y una unidad con UUID únicos, posición 1 y sin prerrequisitos.
}
```

`createDraftCourseForPublishing()` debe llamar a ese helper. Actualizar las publicaciones directas de `Course` en las pruebas de programas para agregar currículo mínimo antes de publicar. No cambiar el comportamiento productivo de programas.

**Step 5: Ejecutar GREEN focal y Academic completo**

```powershell
docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-027-modulos-unidades:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/CourseTest.php modules/Academic/Tests/Feature/PublishCourseTest.php modules/Academic/Tests/Unit/Application/PublishProgramHandlerTest.php modules/Academic/Tests/Unit/Application/ReplaceProgramCoursesHandlerTest.php
```

Expected: PASS; ninguna publicación de curso existente queda rota.

**Step 6: Commit**

```bash
git add modules/Academic/Domain modules/Academic/Tests tests/Pest.php
git commit -m "feat(academic): enforce course curriculum rules"
```

---

### Task 3: Persistencia normalizada y sincronización transaccional

**Files:**

- Create: `modules/Academic/Infrastructure/Persistence/Migrations/2026_08_03_000003_create_academic_course_curriculum_tables.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/CourseModuleModel.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/CourseUnitModel.php`
- Modify: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/CourseModel.php`
- Modify: `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentCourseRepository.php`
- Modify: `modules/Academic/Tests/Integration/EloquentCourseRepositoryTest.php`

**Schema:**

```text
academic_course_modules
  id uuid PK
  course_id uuid FK academic_courses.id ON DELETE CASCADE
  code varchar(60)
  title varchar(180)
  description text
  objectives text nullable
  duration_minutes unsigned integer nullable
  position signed integer
  timestampsTz
  UNIQUE(course_id, code)
  UNIQUE(course_id, position)

academic_course_units
  id uuid PK
  module_id uuid FK academic_course_modules.id ON DELETE CASCADE
  code varchar(60)
  title varchar(180)
  description text
  objectives text nullable
  duration_minutes unsigned integer nullable
  position signed integer
  timestampsTz
  UNIQUE(module_id, code)
  UNIQUE(module_id, position)

academic_module_prerequisites
  module_id uuid FK modules ON DELETE CASCADE
  prerequisite_module_id uuid FK modules ON DELETE CASCADE
  PRIMARY KEY(module_id, prerequisite_module_id)

academic_unit_prerequisites
  unit_id uuid FK units ON DELETE CASCADE
  prerequisite_unit_id uuid FK units ON DELETE CASCADE
  PRIMARY KEY(unit_id, prerequisite_unit_id)
```

**Repository rules:**

- `findById`, `findByCode` y `all` cargan módulos, unidades y prerrequisitos con eager loading y orden explícito.
- `save()` envuelve curso y currículo en `DB::transaction()`.
- Preservar filas de módulos/unidades cuyos UUID siguen presentes; no borrar y recrear indiscriminadamente.
- Antes de reordenar, mover temporalmente las posiciones existentes a valores negativos únicos; después de sincronizar, escribir las posiciones positivas finales. Esto evita colisiones únicas transitorias sin desbordar el tipo de la columna.
- Sincronizar módulos, luego unidades, eliminar nodos obsoletos, aplicar posiciones finales y por último recrear pivotes de prerrequisitos.
- Un UUID que ya pertenece a otro curso no se actualiza: el intento de inserción debe producir y traducirse a un error público 409 `COURSE_CURRICULUM_ID_CONFLICT`; cualquier otra `QueryException` se relanza.

**Step 1: Escribir pruebas RED de integración**

Cubrir:

- Round trip de dos módulos, varias unidades y prerrequisitos.
- Orden después de reordenar preservando UUID.
- Eliminación de nodos obsoletos y pivotes.
- Rollback si un ID pertenece a otro curso.
- Curso publicado legado sin filas curriculares.
- `all()` no produce N+1 ni pierde el currículo reconstruido.

**Step 2: Ejecutar RED**

```powershell
docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-027-modulos-unidades:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Integration/EloquentCourseRepositoryTest.php
```

Expected: FAIL porque no existen tablas/modelos ni reconstrucción curricular.

**Step 3: Implementar migración, relaciones y mapper**

Separar métodos privados del repositorio para `syncModules`, `syncUnits`, `syncPrerequisites` y `toDomain`. No introducir un repositorio separado: el currículo pertenece al agregado `Course`.

**Step 4: Ejecutar GREEN, migración fresca y PHPStan focal**

Repetir Step 2 y ejecutar PHPStan sobre los cinco archivos productivos modificados/creados.

Expected: pruebas PASS, migración compatible con SQLite/PostgreSQL y `[OK] No errors`.

**Step 5: Commit**

```bash
git add modules/Academic/Infrastructure/Persistence modules/Academic/Tests/Integration/EloquentCourseRepositoryTest.php
git commit -m "feat(academic): persist course curriculum"
```

---

### Task 4: Casos de uso de consulta y reemplazo

**Files:**

- Create: `modules/Academic/Application/DTO/CourseModuleInput.php`
- Create: `modules/Academic/Application/DTO/CourseUnitInput.php`
- Create: `modules/Academic/Application/Commands/ReplaceCourseCurriculumCommand.php`
- Create: `modules/Academic/Application/Queries/GetCourseCurriculumQuery.php`
- Create: `modules/Academic/Application/Responses/CourseCurriculumResponse.php`
- Create: `modules/Academic/Application/UseCases/ReplaceCourseCurriculumHandler.php`
- Create: `modules/Academic/Application/UseCases/GetCourseCurriculumHandler.php`
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`
- Test: `modules/Academic/Tests/Unit/Application/CourseCurriculumHandlerTest.php`

**Typed input contracts:**

```php
final readonly class CourseUnitInput
{
    /** @param list<string> $prerequisiteUnitIds */
    public function __construct(
        public string $id,
        public string $code,
        public string $title,
        public string $description,
        public ?string $objectives,
        public ?int $durationMinutes,
        public int $position,
        public array $prerequisiteUnitIds,
    ) {}
}

final readonly class CourseModuleInput
{
    /**
     * @param list<string> $prerequisiteModuleIds
     * @param list<CourseUnitInput> $units
     */
    public function __construct(
        public string $id,
        public string $code,
        public string $title,
        public string $description,
        public ?string $objectives,
        public ?int $durationMinutes,
        public int $position,
        public array $prerequisiteModuleIds,
        public array $units,
    ) {}
}

final readonly class ReplaceCourseCurriculumCommand implements Command
{
    /** @param list<CourseModuleInput> $modules */
    public function __construct(public string $courseId, public array $modules) {}
}
```

`CourseCurriculumResponse::toArray()` devuelve:

```php
array{
    id: string,
    code: string,
    title: string,
    status: string,
    modules: list<array{
        id: string,
        code: string,
        title: string,
        description: string,
        objectives: string|null,
        duration_minutes: int|null,
        position: int,
        prerequisite_module_ids: list<string>,
        units: list<array{
            id: string,
            code: string,
            title: string,
            description: string,
            objectives: string|null,
            duration_minutes: int|null,
            position: int,
            prerequisite_unit_ids: list<string>
        }>
    }>
}
```

**Handler flow:**

1. Parsear `CourseId`.
2. Cargar el curso o lanzar `CourseNotFound`.
3. Mapear inputs a entidades/VO sin mutar el curso.
4. Llamar `replaceCurriculum()`.
5. Guardar una sola vez.
6. Responder desde el agregado guardado.

La consulta carga y responde sin guardar.

**Step 1: Escribir pruebas RED**

Cubrir reemplazo exitoso, consulta, curso inexistente, error de dominio sin `save()`, respuesta ordenada y registro de ambos handlers en el bus.

**Step 2: Ejecutar RED**

```powershell
docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-027-modulos-unidades:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Unit/Application/CourseCurriculumHandlerTest.php
```

Expected: FAIL por clases/handlers inexistentes.

**Step 3: Implementar DTO, comando, query, handlers y response**

Mantener `CourseNotFound` existente y registrar comando/query en `AcademicServiceProvider`.

**Step 4: Ejecutar GREEN y PHPStan focal**

Repetir Step 2 y analizar `modules/Academic/Application` más el provider.

**Step 5: Commit**

```bash
git add modules/Academic/Application modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php modules/Academic/Tests/Unit/Application/CourseCurriculumHandlerTest.php
git commit -m "feat(academic): manage course curriculum"
```

---

### Task 5: API protegida del currículo

**Files:**

- Create: `modules/Academic/Presentation/Http/Requests/ReplaceCourseCurriculumRequest.php`
- Modify: `modules/Academic/Presentation/Http/Controllers/CourseController.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Test: `modules/Academic/Tests/Feature/CourseCurriculumTest.php`

**Routes:**

```php
Route::middleware('permission:courses.view')->group(function (): void {
    Route::get('/courses/{courseId}/curriculum', [CourseController::class, 'curriculum'])
        ->whereUuid('courseId')
        ->name('courses.curriculum.show');
});

Route::middleware('permission:courses.manage')->group(function (): void {
    Route::put('/courses/{courseId}/curriculum', [CourseController::class, 'replaceCurriculum'])
        ->whereUuid('courseId')
        ->name('courses.curriculum.update');
});
```

Ambas permanecen dentro de `auth:sanctum`.

**Form Request rules:**

- `modules`: `present|array|max:200`; permite vacío durante draft.
- IDs: `required|uuid|distinct:ignore_case` en el alcance correspondiente.
- Código: `required|string|max:60|regex` y `distinct:ignore_case` por contenedor.
- Título: `required|string|max:180`.
- Descripción: `required|string|max:5000`.
- Objetivos: `nullable|string|max:5000`.
- Duración: `nullable|integer|min:1|max:525600`.
- Posición: `required|integer|min:1|max:1000000`.
- Prerrequisitos: `present|array|max:200` y elementos `uuid|distinct:ignore_case`.
- Unidades: `present|array|max:500`.

Las reglas HTTP protegen tamaño/forma; pertenencia, orden y referencias siguen en dominio.

**Step 1: Escribir Feature RED**

Flujo principal:

1. Autenticar superadmin.
2. Crear curso draft.
3. `PUT` dos módulos con varias unidades y prerrequisitos.
4. Verificar respuesta normalizada, orden y persistencia.
5. `GET` y verificar igualdad estructural.
6. Publicar y confirmar éxito.
7. Reintentar `PUT` y confirmar `COURSE_CURRICULUM_CANNOT_BE_MODIFIED` sin cambio parcial.

Casos adicionales: sin auth, view vs manage, UUID/códigos duplicados por casing, payload demasiado grande, posición inválida, referencia futura/inexistente, módulo vacío al publicar, curso inexistente y currículo vacío al publicar.

**Step 2: Ejecutar RED**

```powershell
docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-027-modulos-unidades:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Feature/CourseCurriculumTest.php
```

Expected: FAIL 404 porque las rutas no existen.

**Step 3: Implementar request, controller y routes**

El controlador solo transforma arrays validados a `CourseModuleInput`/`CourseUnitInput`, envía al bus y serializa `CourseCurriculumResponse`; no duplica reglas del agregado.

**Step 4: Ejecutar GREEN, rutas y PHPStan focal**

```powershell
docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-027-modulos-unidades:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Feature/CourseCurriculumTest.php modules/Academic/Tests/Feature/PublishCourseTest.php
```

```powershell
docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-027-modulos-unidades:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan route:list --path=api/v1/academic/courses
```

Expected: Feature PASS y las dos rutas curriculares registradas junto con las rutas existentes de cursos.

**Step 5: Commit**

```bash
git add modules/Academic/Presentation modules/Academic/Tests/Feature/CourseCurriculumTest.php
git commit -m "feat(academic): expose course curriculum API"
```

---

### Task 6: Calidad completa y cierre documental

**Files:**

- Modify: `docs/roadmap/ENG-000-roadmap-tecnico-backend.md`
- Modify: `docs/engineering/ENG-LOG.md`

**Step 1: Build frontend**

Desde el worktree:

```powershell
npm ci
npm run build
```

Expected: build Vite PASS y `public/build/manifest.json` presente.

**Step 2: Formatter y calidad completa**

```powershell
docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-027-modulos-unidades:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" --env COMPOSER_PROCESS_TIMEOUT=600 edudrive-app composer format
```

```powershell
docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-027-modulos-unidades:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" --env COMPOSER_PROCESS_TIMEOUT=600 edudrive-app composer quality
```

Registrar conteos exactos de Pint, PHPStan, pruebas y aserciones. Si Pint modifica código, revisar y crear un commit de estilo separado antes de documentación.

**Step 3: Verificar rutas finales**

Ejecutar `route:list --path=api/v1/academic/courses` y registrar el total/contratos.

**Step 4: Actualizar roadmap y ENG-LOG**

- Marcar ENG-027 como `Completado`.
- Agregar nota de alcance regional y ciclo de vida.
- Actualizar historia activa a ENG-028 o pendiente de decisión explícita.
- Agregar `IMP-027` con archivos/tablas/endpoints, invariantes, validaciones y conteos reales.
- Diferir explícitamente: lecciones/multimedia/accesibilidad de contenido (ENG-028), versionado/revisión (ENG-029), progreso/reglas de avance (ENG-035–037), reutilización entre cursos, web y perfiles país.
- Incrementar versión del roadmap a `1.7.0`.

**Step 5: Verificación documental y commit**

```bash
git diff --check
git status --short
git add docs/roadmap/ENG-000-roadmap-tecnico-backend.md docs/engineering/ENG-LOG.md
git commit -m "docs(roadmap): record ENG-027 course curriculum"
```

Expected: worktree limpio.

---

## Revisión final obligatoria

Después de Task 6:

1. Solicitar una revisión integral del rango desde el commit de este plan hasta `HEAD`.
2. Verificar seguridad/autorización, invariantes, atomicidad, sincronización de posiciones, colisiones de UUID, compatibilidad legacy y ausencia de alcance extra.
3. Corregir cualquier hallazgo Critical/Important con TDD y repetir la revisión.
4. Ejecutar `composer quality` completo fresco una última vez.
5. Actualizar conteos documentales si las correcciones agregan archivos/pruebas.
6. Usar `superpowers:finishing-a-development-branch` para ofrecer integración local, push/PR, conservación o descarte.
