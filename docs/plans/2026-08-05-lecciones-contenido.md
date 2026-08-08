# ENG-028 — Lecciones y contenido accesible — Implementation Plan

> **For Codex:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Incorporar a cada unidad curricular un contenido didáctico reemplazable de forma atómica, formado por lecciones y bloques accesibles tipados, y exigir cobertura completa antes de publicar cursos nuevos.

**Architecture:** Mantener Course como autoridad del ciclo de vida y crear UnitContent como agregado separado identificado por CourseUnitId. EloquentUnitContentRepository bloqueará la fila de academic_courses antes de validar pertenencia, estado draft y sincronizar el árbol de una unidad. La publicación usará una nueva mutación atómica de CourseRepository que calcula UnitContentCoverage dentro de la misma transacción y bloqueo. El API solo almacenará referencias HTTPS y metadatos; nunca descargará ni ejecutará recursos remotos.

**Tech Stack:** PHP 8.4, Laravel 12, Eloquent, PostgreSQL/SQLite en pruebas, Pest 3, Sanctum, Larastan/PHPStan y Laravel Pint.

---

## Contexto de ejecución

- Repositorio base: C:\Users\vr506\Documents\EDUDRIVE\edudrive-api
- Worktree: C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido
- Rama: codex/eng-028-lecciones-contenido
- Diseño aprobado: docs/plans/2026-08-05-lecciones-contenido-design.md
- Línea base verificada: Pint 345 archivos, PHPStan 264 archivos, Pest 312 pruebas y 1013 aserciones.
- Warning preexistente no bloqueante: importación DateTimeImmutable sin efecto en modules/Identity/Tests/Feature/LoginWebTest.php:5.

Todos los comandos PHP deben ejecutarse contra el worktree exacto. No usar el contenedor persistente si está montando el checkout principal. La forma canónica es:

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app <comando>

Mantener el ciclo TDD en cada tarea:

1. Escribir una prueba pequeña que exprese una sola regla.
2. Ejecutarla y confirmar RED por la ausencia o conducta incorrecta esperada.
3. Implementar lo mínimo para obtener GREEN.
4. Ejecutar el archivo de pruebas completo de esa capa.
5. Formatear los archivos tocados y comprometer el incremento.

## Contratos que debe preservar la implementación

- UnitContent no se incorpora como colección dentro de Course.
- Lecciones y bloques no se comparten entre unidades.
- PUT reemplaza el contenido completo de una unidad; no existen endpoints granulares.
- Solo draft permite reemplazar contenido.
- GET devuelve lessons: [] cuando una unidad aún no tiene contenido.
- Publicar exige contenido completo para todas las unidades del currículo actual.
- Un curso ya publicado antes de ENG-028 puede restaurarse y consultarse sin contenido.
- Los UUID de lecciones y bloques son globales y no pueden transferirse entre unidades.
- El servidor solo valida y persiste URLs HTTPS; no hace fetch remoto.
- Los interactivos se devuelven como enlaces no confiables, nunca como autorización de iframe.
- No se agregan permisos: se reutilizan courses.view y courses.manage.
- Quedan fuera ENG-029, progreso, perfiles por país, evaluaciones, competencias, SIMUDRIVE, subida de binarios y UI.

---

### Task 1: Modelar bloques de contenido tipados y accesibles

**Files:**

- Create: modules/Academic/Domain/Enums/ContentBlockType.php
- Create: modules/Academic/Domain/ValueObjects/ContentBlockId.php
- Create: modules/Academic/Domain/ValueObjects/ExternalContentUrl.php
- Create: modules/Academic/Domain/Entities/ContentBlocks/ContentBlock.php
- Create: modules/Academic/Domain/Entities/ContentBlocks/TextContentBlock.php
- Create: modules/Academic/Domain/Entities/ContentBlocks/ImageContentBlock.php
- Create: modules/Academic/Domain/Entities/ContentBlocks/VideoContentBlock.php
- Create: modules/Academic/Domain/Entities/ContentBlocks/AudioContentBlock.php
- Create: modules/Academic/Domain/Entities/ContentBlocks/InteractiveContentBlock.php
- Create: modules/Academic/Domain/Entities/ContentBlocks/DownloadContentBlock.php
- Create: modules/Academic/Domain/Services/ContentBlockFactory.php
- Create: modules/Academic/Domain/Exceptions/InvalidContentBlock.php
- Create: modules/Academic/Domain/Exceptions/ContentAccessibilityRequired.php
- Test: modules/Academic/Tests/Unit/Domain/Entities/ContentBlockTest.php

**Step 1: Escribir las pruebas RED del contrato común**

Crear ContentBlockTest.php con datasets para:

- ContentBlockId acepta UUID y rechaza valores inválidos.
- ContentBlockType solo acepta text, image, video, audio, interactive y download.
- position debe ser mayor que cero.
- ExternalContentUrl acepta únicamente HTTPS, rechaza HTTP, otros esquemas, credenciales embebidas, URLs inválidas y más de 2048 caracteres.
- ContentBlockFactory rechaza tipos desconocidos o payloads inconsistentes con INVALID_CONTENT_BLOCK y estado 422.
- Los IDs y tipos se exponen de manera uniforme desde ContentBlock.

**Step 2: Ejecutar la prueba y confirmar RED**

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/Entities/ContentBlockTest.php

Expected: FAIL porque todavía no existen los tipos del dominio.

**Step 3: Implementar el mínimo del contrato común**

- ContentBlockType será un enum backed por string.
- ContentBlock será una interfaz sellada por convención con id(), type(), position() y payload().
- ContentBlockFactory recibirá ContentBlockId, ContentBlockType|string, position y array<string,mixed>, y devolverá una de las seis clases finales.
- InvalidContentBlock expondrá INVALID_CONTENT_BLOCK, HTTP 422.
- ContentAccessibilityRequired expondrá CONTENT_ACCESSIBILITY_REQUIRED, HTTP 422.
- ExternalContentUrl normalizará trim, comprobará longitud y exigirá scheme https y host no vacío. No hará DNS, HTTP ni lectura remota.

**Step 4: Agregar las pruebas RED por tipo**

Cubrir exactamente:

- text: markdown no vacío; título opcional; rechaza HTML arbitrario.
- image: url y alt no vacío; caption opcional.
- video: url, captions_url y transcript no vacío; title/description opcionales.
- audio: url y transcript no vacío; title/description opcionales.
- interactive: url y al menos accessible_text no vacío o accessible_url HTTPS; title/description opcionales.
- download: url, display_name y mime_type; description, filename y size_bytes positivos opcionales.
- Campos obligatorios de accesibilidad producen CONTENT_ACCESSIBILITY_REQUIRED.
- Campos estructurales inválidos, claves ajenas o números no positivos producen INVALID_CONTENT_BLOCK.
- payload() devuelve una representación canónica sin claves desconocidas.

**Step 5: Implementar las seis clases y obtener GREEN**

Mantener validación y normalización dentro de cada clase. No almacenar un array libre internamente; cada clase debe tener propiedades tipadas y construir su payload canónico al responder o persistir.

**Step 6: Ejecutar pruebas y formato**

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/Entities/ContentBlockTest.php

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php vendor/bin/pint modules/Academic/Domain modules/Academic/Tests/Unit/Domain/Entities/ContentBlockTest.php

Expected: PASS.

**Step 7: Commit**

    git add modules/Academic/Domain modules/Academic/Tests/Unit/Domain/Entities/ContentBlockTest.php
    git commit -m "feat(academic): model accessible content blocks"

---

### Task 2: Crear UnitContent y Lesson

**Files:**

- Create: modules/Academic/Domain/Aggregates/UnitContent.php
- Create: modules/Academic/Domain/Entities/Lesson.php
- Create: modules/Academic/Domain/ValueObjects/LessonId.php
- Create: modules/Academic/Domain/Exceptions/InvalidLessonPosition.php
- Create: modules/Academic/Domain/Exceptions/InvalidBlockPosition.php
- Test: modules/Academic/Tests/Unit/Domain/Aggregates/UnitContentTest.php

**Step 1: Escribir las pruebas RED de Lesson y UnitContent**

UnitContentTest.php debe demostrar:

- Una unidad puede representarse con lessons: [] y entonces isComplete() es false.
- Lesson normaliza código con CurriculumCode, título, resumen opcional y duración positiva opcional.
- Cada lección necesita al menos un bloque.
- Posiciones de lecciones son consecutivas desde 1; si no, INVALID_LESSON_POSITION.
- Posiciones de bloques son consecutivas desde 1 por lección; si no, INVALID_BLOCK_POSITION.
- UUID de lección no se repite en una unidad.
- UUID de bloque no se repite en ninguna lección de la unidad.
- Código de lección no se repite por casing dentro de la unidad.
- Construir un candidato inválido no muta un UnitContent previamente válido.
- isComplete() solo es true cuando hay al menos una lección y cada lección contiene bloques válidos.

**Step 2: Confirmar RED**

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/UnitContentTest.php

Expected: FAIL por clases ausentes.

**Step 3: Implementar Lesson y UnitContent**

- Lesson tendrá LessonId, CurriculumCode, title, summary, durationMinutes, position y list<ContentBlock>.
- UnitContent usará CourseUnitId como identidad y list<Lesson>.
- Validar el candidato completo antes de asignarlo.
- Exponer lessons() como lista ordenada y isComplete().
- No agregar UnitContent a las propiedades de Course.

**Step 4: Ejecutar la capa de dominio**

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/UnitContentTest.php modules/Academic/Tests/Unit/Domain/Entities/ContentBlockTest.php

Expected: PASS.

**Step 5: Commit**

    git add modules/Academic/Domain/Aggregates/UnitContent.php modules/Academic/Domain/Entities/Lesson.php modules/Academic/Domain/ValueObjects/LessonId.php modules/Academic/Domain/Exceptions/InvalidLessonPosition.php modules/Academic/Domain/Exceptions/InvalidBlockPosition.php modules/Academic/Tests/Unit/Domain/Aggregates/UnitContentTest.php
    git commit -m "feat(academic): model ordered unit lessons"

---

### Task 3: Persistir el agregado y coordinar cobertura y bloqueos

**Files:**

- Create: modules/Academic/Domain/Repositories/UnitContentRepository.php
- Create: modules/Academic/Domain/ValueObjects/UnitContentCoverage.php
- Create: modules/Academic/Domain/Exceptions/CourseContentCannotBeModified.php
- Create: modules/Academic/Domain/Exceptions/CourseUnitContentRequired.php
- Modify: modules/Academic/Domain/Aggregates/Course.php
- Modify: modules/Academic/Domain/Repositories/CourseRepository.php
- Modify: modules/Academic/Application/UseCases/PublishCourseHandler.php
- Create: modules/Academic/Infrastructure/Persistence/Migrations/2026_08_05_000001_create_academic_unit_content_tables.php
- Create: modules/Academic/Infrastructure/Persistence/Eloquent/Models/UnitContentModel.php
- Create: modules/Academic/Infrastructure/Persistence/Eloquent/Models/LessonModel.php
- Create: modules/Academic/Infrastructure/Persistence/Eloquent/Models/ContentBlockModel.php
- Modify: modules/Academic/Infrastructure/Persistence/Eloquent/Models/CourseUnitModel.php
- Create: modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentUnitContentRepository.php
- Modify: modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentCourseRepository.php
- Create: modules/Academic/Application/Exceptions/CourseContentIdConflict.php
- Create: modules/Academic/Application/Exceptions/CourseUnitNotFound.php
- Modify: modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php
- Create: modules/Academic/Tests/Integration/EloquentUnitContentRepositoryTest.php
- Modify: modules/Academic/Tests/Integration/EloquentCourseRepositoryTest.php
- Modify: modules/Academic/Tests/Unit/Domain/Aggregates/CourseTest.php
- Modify: modules/Academic/Tests/Unit/Application/CourseCurriculumHandlerTest.php
- Modify: modules/Academic/Tests/Unit/Application/PublishProgramHandlerTest.php
- Modify: modules/Academic/Tests/Unit/Application/ReplaceProgramCoursesHandlerTest.php

**Step 1: Definir el puerto de repositorio**

UnitContentRepository debe exponer:

    public function findForCourseUnit(
        CourseId $courseId,
        CourseUnitId $unitId
    ): ?UnitContent;

    public function replaceAtomically(
        CourseId $courseId,
        CourseUnitId $unitId,
        UnitContent $content
    ): ?UnitContent;

Contrato:

- null significa que el curso no existe.
- Curso existente con unidad inexistente o ajena lanza CourseUnitNotFound.
- Reemplazo en published/archived lanza CourseContentCannotBeModified.
- UUID ajeno lanza CourseContentIdConflict.
- find devuelve UnitContent vacío cuando la unidad existe pero no tiene fila de contenido.

**Step 2: Escribir las pruebas RED de migración y round trip**

EloquentUnitContentRepositoryTest.php:

- Guarda un curso con currículo usando EloquentCourseRepository.
- find devuelve agregado vacío para una unidad existente sin contenido.
- replaceAtomically crea unit content, dos lecciones y los seis tipos de bloque.
- Round trip reconstruye clases tipadas y payloads canónicos en orden.
- Las tablas tienen FK cascade, unicidad de code/position y CHECK de duration_minutes positivo.
- Compilar el CHECK con PostgresGrammar como hace EloquentCourseRepositoryTest.
- payload se persiste como JSON, pero el repositorio nunca devuelve arrays libres al dominio.

**Step 3: Ejecutar RED**

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Integration/EloquentUnitContentRepositoryTest.php

Expected: FAIL por migración/repositorio ausentes.

**Step 4: Crear migración, modelos y mapper**

- academic_unit_contents: unit_id PK/FK academic_course_units.id, timestampsTz.
- academic_lessons: UUID PK, unit_id FK, code 60, title 180, summary nullable, duration_minutes CHECK positivo nullable, position, timestampsTz, unique(unit_id, code), unique(unit_id, position).
- academic_lesson_blocks: UUID PK, lesson_id FK, type 30, position, payload JSON, timestampsTz, unique(lesson_id, position).
- Relaciones eager: UnitContentModel lessons; LessonModel blocks; CourseUnitModel content.
- casts: posiciones/duración enteros, payload array.
- Reconstruir bloques mediante ContentBlockFactory.

**Step 5: Implementar reemplazo atómico mínimo**

En una única DB::transaction:

1. Cargar CourseModel por id con lockForUpdate.
2. Si no existe, devolver null.
3. Reconstruir el Course canónico mediante CourseRepository y llamar ensureContentCanBeModified().
4. Confirmar ownsUnit(unitId); si no, CourseUnitNotFound.
5. Confirmar que content->unitId coincide con unitId.
6. Comprobar ownership global de todos los LessonId y ContentBlockId entrantes.
7. Mover códigos y posiciones existentes a valores temporales.
8. Upsert solo dentro de la unidad propietaria.
9. Eliminar bloques/lecciones obsoletos.
10. Aplicar código y posiciones finales.
11. Recargar con eager loading y devolver el agregado canónico antes de commit.

Traducir solo las restricciones PK de academic_lessons y academic_lesson_blocks a COURSE_CONTENT_ID_CONFLICT, HTTP 409. No ocultar otras QueryException.

**Step 6: Agregar pruebas RED de reordenamiento, rollback y ownership**

Cubrir:

- Reordenar e intercambiar códigos preserva UUID y no provoca colisiones transitorias.
- Eliminar lecciones/bloques obsoletos hace cascade correcto.
- Un LessonId o ContentBlockId de otra unidad no se transfiere.
- Una inserción competidora entre ownership check y sync se traduce a 409 y revierte todo.
- Un bloque inválido al restaurar revierte y conserva contenido anterior.
- Curso/unidad ajena producen los errores públicos pactados.
- Reemplazo después de publish/archive se rechaza sin mutar filas.
- La consulta usa un número acotado de queries, sin N+1.

**Step 7: Integrar cobertura bajo el bloqueo de publicación**

Primero agregar pruebas RED a CourseTest.php:

- Course::ownsUnit(CourseUnitId) distingue unidades del currículo actual.
- Course::ensureContentCanBeModified() permite draft y rechaza published/archived con COURSE_CONTENT_CANNOT_BE_MODIFIED.
- publish requiere UnitContentCoverage con todos los CourseUnitId actuales.
- Falta de una unidad produce COURSE_UNIT_CONTENT_REQUIRED y no cambia status ni publishedAt.
- Cobertura completa publica normalmente.
- Course::restore mantiene compatibilidad con un curso ya published sin currículo o contenido.

Luego implementar:

- UnitContentCoverage como value object inmutable de CourseUnitId, deduplicado por valor.
- Course::publish(DateTimeImmutable, UnitContentCoverage), conservando primero las validaciones de archived/already published/currículo/módulos y exigiendo después cobertura para cada unidad.
- Course::ownsUnit() y Course::ensureContentCanBeModified().
- CourseRepository mantiene updateAtomically y añade updateAtomicallyWithContentCoverage con Closure(Course, UnitContentCoverage): void.
- PublishCourseHandler usa exclusivamente el nuevo método.
- La cobertura no es opcional para publicar un draft nuevo; legacy solo aplica a Course::restore de un curso que ya está published.

En EloquentCourseRepository::updateAtomicallyWithContentCoverage:

- Reutilizar queryWithCurriculum(), transaction y lockForUpdate.
- Calcular dentro de la misma transacción los unit_id completos:
  - existe al menos una lección;
  - no existe ninguna lección sin bloques.
- Construir UnitContentCoverage.
- Ejecutar la closure con Course y cobertura.
- Persistir y recargar Course de manera canónica.

Agregar a EloquentCourseRepositoryTest:

- Publish bajo bloqueo rechaza cobertura parcial y no cambia status.
- Con todas las unidades completas publica.
- Publish antes de replace deja publicado y el replace posterior falla.
- Replace antes de publish persiste exactamente el contenido nuevo y luego publica.
- Restore de curso published legacy sin contenido continúa permitido.

Actualizar todas las implementaciones de CourseRepository y llamadas directas a Course::publish afectadas en:

- CourseCurriculumHandlerTest.php.
- PublishProgramHandlerTest.php.
- ReplaceProgramCoursesHandlerTest.php.
- EloquentCourseRepositoryTest.php.

Cada fake debe implementar updateAtomicallyWithContentCoverage de forma explícita. Los helpers de pruebas que publican cursos deben construir cobertura completa a partir de las unidades del currículo; no usar una cobertura implícita o un bypass para tests.

**Step 8: Ejecutar integración**

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/CourseTest.php modules/Academic/Tests/Unit/Application/CourseCurriculumHandlerTest.php modules/Academic/Tests/Unit/Application/PublishProgramHandlerTest.php modules/Academic/Tests/Unit/Application/ReplaceProgramCoursesHandlerTest.php modules/Academic/Tests/Integration/EloquentUnitContentRepositoryTest.php modules/Academic/Tests/Integration/EloquentCourseRepositoryTest.php

Expected: PASS.

**Step 9: Commit**

    git add modules/Academic/Domain modules/Academic/Application/Exceptions modules/Academic/Application/UseCases/PublishCourseHandler.php modules/Academic/Infrastructure/Persistence modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php modules/Academic/Tests/Integration modules/Academic/Tests/Unit/Domain/Aggregates/CourseTest.php modules/Academic/Tests/Unit/Application
    git commit -m "feat(academic): persist unit content atomically"

---

### Task 4: Implementar comandos, consultas y respuestas de contenido

**Files:**

- Create: modules/Academic/Application/DTO/LessonInput.php
- Create: modules/Academic/Application/DTO/ContentBlockInput.php
- Create: modules/Academic/Application/Commands/ReplaceUnitContentCommand.php
- Create: modules/Academic/Application/Queries/GetUnitContentQuery.php
- Create: modules/Academic/Application/Responses/UnitContentResponse.php
- Create: modules/Academic/Application/UseCases/ReplaceUnitContentHandler.php
- Create: modules/Academic/Application/UseCases/GetUnitContentHandler.php
- Modify: modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php
- Create: modules/Academic/Tests/Unit/Application/UnitContentHandlerTest.php

**Step 1: Escribir el fake y las pruebas RED**

UnitContentHandlerTest.php debe incluir un fake de CourseRepository y otro de UnitContentRepository que imiten clone/commit atómico.

Casos:

- Replace construye bloques tipados con ContentBlockFactory y llama una sola vez replaceAtomically.
- Respuesta normaliza código, textos, URLs y orden.
- Get devuelve lessons: [] para una unidad existente sin contenido.
- Curso inexistente produce COURSE_NOT_FOUND.
- Unidad inexistente o ajena produce COURSE_UNIT_NOT_FOUND sin filtrar ownership.
- Curso published/archived produce COURSE_CONTENT_CANNOT_BE_MODIFIED.
- Un dominio inválido no guarda ni altera el contenido anterior.
- CommandBus y QueryBus reales resuelven ambos handlers desde AcademicServiceProvider.

**Step 2: Confirmar RED**

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Unit/Application/UnitContentHandlerTest.php

Expected: FAIL por mensajes y handlers ausentes.

**Step 3: Implementar DTO y mensajes**

- LessonInput: id, code, title, summary, durationMinutes, position, list<ContentBlockInput>.
- ContentBlockInput: id, type, position, array<string,mixed> payload ya validado en Presentation.
- ReplaceUnitContentCommand implementa Command.
- GetUnitContentQuery implementa Query.

**Step 4: Implementar handlers y respuesta**

- Get carga primero Course para diferenciar COURSE_NOT_FOUND; verifica ownsUnit; luego consulta UnitContentRepository.
- Replace crea UnitContent candidato con bloques tipados antes de persistir y delega la verificación definitiva de estado/pertenencia al reemplazo bloqueado.
- UnitContentResponse incluye:
  - course_id
  - unit_id
  - course_status
  - lessons ordenadas con blocks y payload canónico.
- La respuesta no añade HTML renderizado, embed, URL resuelta ni estado remoto.

**Step 5: Registrar en AcademicServiceProvider**

- Bind UnitContentRepository a EloquentUnitContentRepository en register().
- Registrar ReplaceUnitContentCommand y GetUnitContentQuery en MessageHandlerRegistry.

**Step 6: Ejecutar GREEN y regresión de aplicación**

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Unit/Application

Expected: PASS.

**Step 7: Commit**

    git add modules/Academic/Application modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php modules/Academic/Tests/Unit/Application/UnitContentHandlerTest.php
    git commit -m "feat(academic): add unit content use cases"

---

### Task 5: Publicar la API protegida y validar payloads temprano

**Files:**

- Create: modules/Academic/Presentation/Http/Requests/ReplaceUnitContentRequest.php
- Modify: modules/Academic/Presentation/Http/Controllers/CourseController.php
- Modify: modules/Academic/Presentation/Routes/api.php
- Create: modules/Academic/Tests/Feature/CourseUnitContentTest.php
- Modify: modules/Academic/Tests/Feature/PublishCourseTest.php

**Step 1: Escribir el flujo Feature RED**

CourseUnitContentTest.php debe preparar un curso draft con currículo real y ejecutar:

1. GET inicial devuelve lessons: [].
2. PUT con varias lecciones y los seis tipos devuelve 200 y forma canónica.
3. GET posterior devuelve exactamente data del PUT.
4. Publish falla con COURSE_UNIT_CONTENT_REQUIRED mientras otra unidad no tenga contenido.
5. Completar todas las unidades permite publicar.
6. PUT posterior a publish falla y conserva el contenido.

**Step 2: Confirmar RED**

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Feature/CourseUnitContentTest.php

Expected: FAIL 404 porque las rutas aún no existen.

**Step 3: Agregar rutas y controller**

Rutas:

    GET /api/v1/academic/courses/{courseId}/units/{unitId}/content
    PUT /api/v1/academic/courses/{courseId}/units/{unitId}/content

- Ambas dentro de auth:sanctum.
- GET dentro de permission:courses.view.
- PUT dentro de permission:courses.manage.
- whereUuid para ambos parámetros.
- Nombres courses.units.content.show y courses.units.content.update.
- CourseController debe mapear el payload validado a LessonInput/ContentBlockInput y despachar QueryBus/CommandBus.

**Step 4: Diseñar validación temprana**

ReplaceUnitContentRequest:

- prepareForValidation hace un recorrido lineal antes de crear reglas anidadas.
- Rechaza más de 100 lecciones con un único error lessons.
- Rechaza más de 200 bloques en una lección con un único error lessons.
- Rechaza más de 1000 bloques totales con un único error lessons.
- Si se excede un tope, rules() no expande lessons.*.blocks.*.
- Reglas base:
  - lessons present|array|max:100
  - UUID de lección globalmente distintos por casing.
  - code requerido, formato CurriculumCode, max 60 y distinto por casing en la unidad.
  - title requerido max 180.
  - summary nullable con límite explícito.
  - duration_minutes nullable integer min 1.
  - position requerido integer positivo.
  - blocks present|array|min:1|max:200.
  - UUID de bloque globalmente distintos por casing en toda la unidad.
  - type requerido y en ContentBlockType.
  - block position requerido integer positivo.
- Reglas discriminadas por type:
  - text: markdown y title opcional.
  - image: url HTTPS max 2048, alt, caption opcional.
  - video: url y captions_url HTTPS, transcript, title/description opcionales.
  - audio: url HTTPS, transcript, title/description opcionales.
  - interactive: url HTTPS y accessible_text o accessible_url HTTPS.
  - download: url HTTPS, display_name, mime_type, description/filename opcionales y size_bytes positivo opcional.
- Rechazar arrays/objetos donde se esperan escalares con VALIDATION_ERROR, nunca TypeError/500.
- Las invariantes de posiciones siguen en dominio.

Usar límites explícitos coherentes con el body global: title 180, code 60, URL 2048 y textos extensos 50 000 caracteres salvo que el diseño global del request configure un máximo menor.

**Step 5: Agregar matriz Feature RED/GREEN**

Cubrir:

- Sin autenticación: 401 en GET/PUT.
- Teacher: GET 200, PUT 403.
- SuperAdmin: GET/PUT 200.
- courseId inexistente: COURSE_NOT_FOUND.
- unitId inexistente o perteneciente a otro curso: COURSE_UNIT_NOT_FOUND.
- IDs/códigos duplicados por casing.
- Posiciones no consecutivas delegadas al dominio.
- Un caso inválido por cada tipo.
- HTTP, file, javascript y URL malformada rechazadas.
- Accesibilidad faltante produce validación 422 o CONTENT_ACCESSIBILITY_REQUIRED según llegue a dominio.
- HTML arbitrario en markdown produce INVALID_CONTENT_BLOCK.
- Topes 101 lecciones, 201 bloques/lección y 1001 bloques totales cortan temprano.
- Exactamente 100 lecciones, 200 bloques/lección y 1000 bloques totales se aceptan en payloads separados que no contradigan los otros topes.
- UUID ajeno produce COURSE_CONTENT_ID_CONFLICT y rollback.
- GET de curso published legacy sin contenido sigue devolviendo lessons: [].

**Step 6: Ejecutar Feature y rutas**

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Feature/CourseUnitContentTest.php modules/Academic/Tests/Feature/PublishCourseTest.php

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan route:list --path=api/v1/academic/courses -v --except-vendor

Expected: pruebas PASS; aparecen 8 rutas de cursos, incluidas las dos nuevas con Sanctum y permisos correctos.

**Step 7: Commit**

    git add modules/Academic/Presentation modules/Academic/Tests/Feature
    git commit -m "feat(academic): expose protected unit content api"

---

### Task 6: Endurecer concurrencia, seguridad y compatibilidad

**Files:**

- Modify: modules/Academic/Tests/Integration/EloquentUnitContentRepositoryTest.php
- Modify: modules/Academic/Tests/Integration/EloquentCourseRepositoryTest.php
- Modify: modules/Academic/Tests/Feature/CourseUnitContentTest.php
- Modify only if tests expose a defect:
  - modules/Academic/Domain/Aggregates/Course.php
  - modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentCourseRepository.php
  - modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentUnitContentRepository.php
  - modules/Academic/Presentation/Http/Requests/ReplaceUnitContentRequest.php

**Step 1: Añadir pruebas de serialización**

Usar el patrón QueryExecuted ya presente para simular los órdenes:

- replace obtiene lock, termina y publish ve cobertura nueva.
- publish obtiene lock, cambia status y replace ve published.
- archive obtiene lock y replace ve archived.
- Una alta competidora con UUID global ajeno no transfiere ownership.

Con SQLite se verifica orden lógico y rollback. Registrar como validación futura no bloqueante una contención multiconexión sobre PostgreSQL real si el entorno de pruebas no permite dos conexiones concurrentes.

**Step 2: Añadir pruebas de seguridad**

- Afirmar que ningún handler o repositorio usa Http, curl, fopen remoto o cliente HTTP.
- Persistir URLs de dominios arbitrarios sin resolverlas.
- Responder interactivos solo como url/alternativa; no generar iframe ni HTML.
- No reflejar payload desconocido en la respuesta canónica.
- No filtrar si un unitId pertenece a otro curso.

**Step 3: Ejecutar los archivos endurecidos**

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test modules/Academic/Tests/Integration/EloquentUnitContentRepositoryTest.php modules/Academic/Tests/Integration/EloquentCourseRepositoryTest.php modules/Academic/Tests/Feature/CourseUnitContentTest.php

Expected: PASS.

**Step 4: Ejecutar análisis estático temprano**

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php vendor/bin/phpstan analyse modules/Academic --memory-limit=1G

Expected: No errors.

**Step 5: Commit**

    git add modules/Academic
    git commit -m "test(academic): harden content lifecycle consistency"

---

### Task 7: Calidad completa, migraciones y trazabilidad

**Files:**

- Modify: docs/roadmap/ENG-000-roadmap-tecnico-backend.md
- Modify: docs/engineering/ENG-LOG.md
- Modify: modules/Academic/README.md only if it will contain useful module-level API guidance; do not add an empty placeholder update.

**Step 1: Ejecutar formato con escritura**

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php vendor/bin/pint

Revisar el diff y confirmar que Pint solo hizo cambios de estilo pertinentes.

**Step 2: Verificar migraciones en entorno aislado**

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan migrate:status

Además, las pruebas de integración deben ejecutar fresh migrations sobre SQLite y verificar la compilación PostgreSQL de los CHECK. No ejecutar migrate destructivo sobre una base compartida.

**Step 3: Ejecutar calidad completa**

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php vendor/bin/pint --test

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php vendor/bin/phpstan analyse --memory-limit=1G

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan config:clear --ansi

    docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-028-lecciones-contenido:/var/www/html" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\vendor:/var/www/html/vendor" --volume "C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.env:/var/www/html/.env:ro" edudrive-app php artisan test tests modules

Expected: todos los comandos terminan con exit 0. Registrar conteos reales, no reutilizar los de la línea base.

**Step 4: Actualizar roadmap**

En ENG-000:

- Versión 1.8.0 y fecha 2026-08-05.
- ENG-028 Estado: Completado.
- Añadir nota de alcance: UnitContent separado, lecciones/bloques tipados, referencias HTTPS, accesibilidad, reemplazo atómico y publicación con cobertura.
- Dejar explícitamente diferidos ENG-029, uploads, UI/player, progreso, asociaciones y perfiles nacionales.
- Sección 25: historia activa pendiente de decisión entre ENG-029 o volver a Fase 4, salvo nueva decisión del usuario.
- Control de cambios 1.8.0.

**Step 5: Actualizar ENG-LOG**

Agregar IMP-028 con:

- agregado, entidades, tipos de bloque y errores públicos;
- tablas y sincronización;
- API y permisos reutilizados;
- serialización replace/publish/archive;
- límites y seguridad sin fetch remoto;
- compatibilidad legacy;
- validaciones reales y warning preexistente;
- alcance diferido.

**Step 6: Revisar diff y estado**

    git status --short
    git diff --check
    git diff --stat
    git diff

Confirmar que no se incluyeron node_modules, public/build, .env, caches ni cambios ajenos.

**Step 7: Commit final de documentación**

    git add docs/roadmap/ENG-000-roadmap-tecnico-backend.md docs/engineering/ENG-LOG.md modules/Academic/README.md
    git commit -m "docs(roadmap): complete ENG-028 lesson content"

Si README no cambió, omitirlo del git add.

**Step 8: Verificación final del HEAD**

    git status --short --branch
    git log --oneline --decorate -8

El worktree debe quedar limpio. Antes de integrar, usar la habilidad superpowers:verification-before-completion y luego superpowers:finishing-a-development-branch.

---

## Criterios de aceptación finales

- Los dos endpoints existen, están autenticados y usan los permisos correctos.
- PUT/GET producen la misma representación canónica.
- Los seis tipos tienen contrato tipado y accesibilidad obligatoria.
- Solo se aceptan referencias HTTPS y nunca se consultan remotamente.
- Reordenar preserva UUID; borrar elimina obsoletos; ownership ajeno da 409.
- Una unidad ajena no se distingue públicamente de una inexistente.
- Un curso no publica hasta cubrir todas sus unidades.
- Replace, publish y archive son consistentes bajo el mismo lock de curso.
- Los cursos published legacy siguen siendo consultables.
- No se implementó ninguna parte de ENG-029 ni alcance expresamente diferido.
- Pint, PHPStan, Pest, rutas y migraciones quedan verificados.
- Roadmap y ENG-LOG reflejan los conteos y el estado real.
