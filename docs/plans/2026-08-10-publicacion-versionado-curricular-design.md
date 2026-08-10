# ENG-029 — Publicación y versionado curricular — Diseño

**Fecha:** 2026-08-10
**Estado:** Aprobado
**Ámbito:** Ciclo de vida de publicación y versionado de cursos del catálogo educativo

## Objetivo

Completar el ciclo de vida de publicación del agregado `Course` y agregar un historial de versiones inmutables:

```text
draft ──submit──▶ under_review ──approve──▶ approved ──publish──▶ published
   ▲                  │                        │                        │
   └─────send_back────┴──────send_back─────────┘                        │
                                                                        │
   archived ◀───────────────────────────────────────────────────────────┘
```

ENG-029 introduce dos estados intermedios (`under_review`, `approved`) entre el borrador y la publicación, y registra un snapshot inmutable del curso completo (datos generales + currículo + contenido por unidad) cada vez que se publica. La revisión y la aprobación usan el mismo permiso existente `courses.manage`; no se crean roles nuevos ni workflow multinivel.

## Decisiones principales

- `Course` mantiene un único agregado mutable (el borrador de trabajo).
- `publish()` del dominio exige que el curso esté `approved`.
- Cada publicación congela un snapshot inmutable en `academic_course_versions`.
- Las versiones son de solo lectura; nunca se modifican tras escribirse.
- Reabrir un curso publicado (`reopen`) lo devuelve a `draft` para construir la siguiente versión sin borrar las publicadas.
- Los endpoints de consulta existentes (`GET curriculum`, `GET unit content`) siguen leyendo el draft mutable. La lectura de estudiantes desde la versión publicada llega con Fase 7 (Progreso).
- Revisión/aprobación simple: submit → approve → publish, con `sendBackToDraft` para revertir. No hay actores ni permisos separados.

## Modelo de dominio

### CourseStatus

Se agregan los casos `UnderReview` y `Approved`:

```text
Draft, UnderReview, Approved, Published, Archived
```

### Transiciones de Course

- `submitForReview()`: `draft` → `under_review`. Rechaza si no está `draft`.
- `approve()`: `under_review` → `approved`. Rechaza si no está `under_review`.
- `sendBackToDraft()`: `under_review` o `approved` → `draft`. Rechaza otros estados.
- `reopen()`: `published` → `draft` (conserva el historial). Rechaza si no está `published`.
- `publish($at, $coverage)`: ahora exige `approved`; conserva la validación de currículo y cobertura de contenido de ENG-028.
- `archive($at)`: sin cambios, permitido desde cualquier estado no archivado.

### CourseVersion

- Identidad: UUID técnico global.
- `courseId` y `versionNumber` (secuencial por curso, desde 1).
- `status`: `published` o `archived`.
- `snapshot`: representación canónica completa del curso en el momento de la publicación.
- Inmutable una vez persistida.

## Persistencia

Nueva tabla:

```text
academic_course_versions
  id              uuid PK
  course_id       uuid FK academic_courses.id ON DELETE CASCADE
  version_number  integer
  status          varchar(30)           -- published | archived
  snapshot        jsonb                 -- curso completo congelado
  published_at    timestamptz
  timestampsTz
  UNIQUE(course_id, version_number)
```

El snapshot contiene metadatos del curso, módulos/unidades con prerrequisitos y, por unidad, las lecciones y bloques de contenido tal y como estaban al publicar. Se construye dentro de la misma transacción que bloquea la fila del curso.

## API

Todas con `auth:sanctum`:

| Método/URL | Permiso | Acción |
|---|---|---|
| `POST /api/v1/academic/courses/{courseId}/submit-for-review` | `courses.manage` | draft → under_review |
| `POST /api/v1/academic/courses/{courseId}/approve` | `courses.manage` | under_review → approved |
| `POST /api/v1/academic/courses/{courseId}/send-back-to-draft` | `courses.manage` | under_review/approved → draft |
| `POST /api/v1/academic/courses/{courseId}/reopen` | `courses.manage` | published → draft |
| `POST /api/v1/academic/courses/{courseId}/publish` | `courses.manage` | approved → published + snapshot |
| `GET /api/v1/academic/courses/{courseId}/versions` | `courses.view` | lista de versiones |
| `GET /api/v1/academic/courses/{courseId}/versions/{versionNumber}` | `courses.view` | snapshot de una versión |

## Errores públicos

- `COURSE_REVIEW_STATE_INVALID` — 422, transición ilegal de estado.
- `COURSE_CANNOT_BE_REOPENED` — 422, reopen de un curso no publicado.
- `COURSE_VERSION_NOT_FOUND` — 404, curso o versión inexistentes (sin distinguir).
- `COURSE_ALREADY_PUBLISHED`, `COURSE_ALREADY_ARCHIVED` — se mantienen.

## Transaccionalidad

La publicación captura el snapshot dentro del mismo lock de fila que ya usa `CourseRepository::updateAtomicallyWithContentCoverage`, garantizando que el cambio de estado y el snapshot sean atómicos y consistentes con el contenido vigente.

## Estrategia de pruebas

1. **Dominio:** transiciones válidas/inválidas, `publish` exige `approved`, `CourseVersion` inmutable con número secuencial y snapshot canónico.
2. **Aplicación:** handlers con curso inexistente (404), transición ilegal (422), permisos; `PublishCourseHandler` captura snapshot atómico; queries de historial vacío/poblado.
3. **Persistencia:** ida y vuelta del snapshot, unicidad de `(course_id, version_number)`, inmutabilidad, ausencia de N+1, rollback.
4. **Feature (HTTP):** flujo submit → approve → publish → reopen → publish (v2), historial, casos 401/403/404/422, y endpoints de consulta que siguen leyendo el draft.
5. **Compatibilidad:** curso publicado legacy consultable, curso publicado pre-ENG-029 reabre correctamente.

## Ajustes a código existente

- `Course::publish()` exige `approved` → ajustar `PublishCourseTest`, helpers de `tests/Pest.php` y el panel web de cursos si publica directo.
- `CourseStatus` gana `UnderReview`/`Approved`.

## Fuera de alcance

- Revisión multinivel con roles nuevos (Coordinador, Evaluador) — ENG-012.
- Permisos separados `courses.review`/`courses.approve`.
- Lectura de estudiantes desde la versión publicada — Fase 7 (Progreso).
- Inscripciones y consumo público del curso por estudiantes.
- Despublicar automático o caducidad de versiones.
- Versionado de programas educativos.
