> **Continuidad actual (28 de septiembre de 2026):** consultar [CONTEXTO.md](../../CONTEXTO.md). El contenido siguiente conserva el estado histórico del 16 de agosto y no representa por sí solo la versión publicada ni el trabajo actual.

# SESION — Registro de estado de trabajo en curso

> Archivo de continuidad: guarda el estado vivo de la sesión para retomarla si la conversación se rompe.
> Se actualiza al inicio y al final de cada sesión. No reemplaza a `ENG-LOG.md` (historial al cierre) ni al roadmap (plan maestro).

---

## Última actualización

- **Fecha:** 2026-08-16
- **Sesión:** Cierre completo de ENG-036 (Seguimiento de progreso, commiteado) + diseño y plan de ENG-037 (Reglas de avance, commiteados, **implementación aún no iniciada**).
- **Nota para quien retome:** el usuario va a continuar la implementación de ENG-037 desde otra sesión/cliente de Claude Code (posiblemente la app directa en vez de la extensión de VSCode). Todo lo necesario para retomar está commiteado en git — no hay nada suelto en el árbol de trabajo de esta sesión que dependa de esta conversación puntual.

---

## Hito activo

- **ENG-037 — Reglas de avance**
- **Fase actual:** diseño y plan de implementación completos y commiteados; **implementación (7 tareas) sin empezar**.
- **Depende de:** ENG-027 (prerrequisitos ya modelados en `Course`/`CourseModule`/`CourseUnit`) y ENG-036 (`EnrollmentProgress`, ya cerrado) — ambos ya disponibles.
- **Docs de referencia:**
  - `docs/plans/2026-08-16-reglas-avance-eng037-design.md`
  - `docs/plans/2026-08-16-reglas-avance-eng037-implementation.md` (plan de 7 tareas, TDD, con código completo por tarea)
- **Cómo retomar:** decir "implementa el plan `docs/plans/2026-08-16-reglas-avance-eng037-implementation.md`" (o simplemente "retoma donde quedamos", este archivo + el plan bastan). La sesión anterior usó el skill `subagent-driven-development` (implementador + revisor de spec + revisor de calidad por tarea) para ENG-036; se recomienda el mismo enfoque aquí, pero cualquier ejecución del plan sirve.

---

## Estado real del árbol de trabajo (verificado 2026-08-16)

- **ENG-036 (Seguimiento de progreso) — Completado y 100% commiteado.** `EnrollmentProgress`/`LessonCompletion`, `CourseLessonCatalog`, persistencia (`academic_enrollment_lesson_completions`, con FK real a `academic_lessons`), `EnrollmentProgressCalculator` (sin N+1), CQRS (`CompleteLessonCommand`/`GetEnrollmentProgressQuery`) y 2 endpoints HTTP. 46 pruebas en verde, Pint/PHPStan limpios. Ver `ENG-LOG.md` (IMP-036) y `docs/roadmap/ENG-000-roadmap-tecnico-backend.md` (v1.15.0).
  - Limitación conocida y aceptada por el usuario: la FK en cascada de `lesson_id` borra en silencio el historial de avance si un docente elimina una lección del currículo. Documentado en el diseño, sin acción pendiente.
- **ENG-037 (Reglas de avance) — Solo diseño + plan, sin código todavía.** Ver hito activo arriba.
- **Deuda de consolidación que SIGUE pendiente** (sin cambios desde antes, no tocada en esta sesión):
  - `ENG-032` (Intentos de evaluación) y `ENG-033` (Motor de calificación): el roadmap los marca "Completado" pero el código real **sigue sin commitear** en el árbol de trabajo (verificado directamente).
  - `ENG-034` (Examen teórico de conducción): roadmap dice "En validación" por la misma razón — implementado, sin commitear.
  - `ENG-035` (Inscripciones): el roadmap dice "Pendiente", pero el dominio/aplicación/persistencia de `Enrollment` ya está implementado (sin commitear); **la API HTTP de ENG-035 sí se commiteó** en la sesión anterior (2026-08-15).
  - La consolidación de todo esto en commits coherentes se ha pospuesto explícitamente varias veces por decisión del usuario. Sigue disponible como tarea futura si se retoma.

---

## Decisiones clave de ENG-036 (para referencia al construir ENG-037 encima)

- `EnrollmentProgress` opera **a nivel de lección** (no de unidad/módulo) — `completedLessonIds()`, `totalTimeSpentMinutes()`, `lastCompletedAt()`.
- `EnrollmentProgressCalculator` cruza esas lecciones con `CourseLessonCatalog` (total de lecciones del curso) y con `ExamAttempt`/`Exam` (evaluaciones del mismo curso, sin N+1) para calcular `progress_percentage`, `evaluations_completed`, `last_activity_at`.
- Autorización ya establecida: dueño del enrollment o permiso `enrollments.view` (reutilizado, sin permisos nuevos) — ENG-037 sigue el mismo patrón.
- `CompleteLessonHandler`/`GetEnrollmentProgressHandler` son los handlers que ENG-037 extiende/complementa (el primero se modifica para bloquear unidades bloqueadas; el segundo no se toca).

## Decisiones clave del diseño de ENG-037

- **Alcance reducido, acordado explícitamente con el usuario:**
  - Solo prerrequisitos por **lecciones completadas** (no por puntaje mínimo de examen — `Exam` no está anclado a unidad/módulo hoy, vincularlo sería una historia propia más grande).
  - **Todas las lecciones son obligatorias** (no se agrega distinción obligatoria/opcional a `Lesson`).
  - **"Rutas adaptativas" se difiere por completo** (sin definición previa en ningún lado; probablemente ENG-039).
  - **Enforcement en ambos lados:** nuevo endpoint de consulta (`GET .../curriculum`) + bloqueo real en `CompleteLessonHandler` (nueva excepción `UnitLocked`, 422).
- Nuevo servicio de dominio `CourseCurriculumUnlockCalculator` (deriva todo en memoria, nada se persiste): un módulo está desbloqueado si sus `prerequisiteModuleIds` están completados; una unidad está desbloqueada si su módulo padre está desbloqueado **y** sus `prerequisiteUnitIds` están completados (pueden cruzar módulos, ya que `Course` acumula `unitIds` de forma global).

---

## Comandos y convenciones operativas

- **CLI siempre en contenedor desechable** (la imagen fija monta copia obsoleta — no usarla directamente):
  `MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan <cmd>`
- **PHPStan:** `vendor/bin/phpstan analyse --no-progress --memory-limit=1G` (lento; timeout generoso)
- **Pint:** `vendor/bin/pint` antes de cada commit
- **Commit style:** `feat(academic): …`, `feat(authorization): …`, `docs(engineering): …`, `test(academic): …`, `fix(academic): …` — tras cada tarea en verde
- **Errores de dominio:** extender `Modules\Foundation\Domain\Exceptions\DomainException`; reutilizar excepciones ya existentes cuando el caso encaje (`EnrollmentNotFound`, `InvalidEnrollment`, `LessonNotFound`) en vez de crear una nueva
- **Git — regla crítica aprendida esta sesión:** `git commit` commitea TODO el índice, no solo lo que acabas de `git add`. Este árbol tiene mucho trabajo ajeno sin commitear (ver "deuda de consolidación" arriba). Antes de cada commit: `git status --short` y confirmar que el bloque staged contiene *exactamente* los archivos de la tarea en curso. Nunca `git add -A` ni `git add .`.
- **CLI en Windows:** vigilar artefacto `nul` (se crea con redirecciones MSYS) y borrarlo si aparece

---

## Próximos pasos (para retomar)

1. Ejecutar las 7 tareas de `docs/plans/2026-08-16-reglas-avance-eng037-implementation.md` (TDD, commits frecuentes).
2. Verificación final (Task 7 del plan): suite completa, Pint, PHPStan, `route:list`, actualizar roadmap/ENG-LOG (IMP-037).
3. Una vez cerrado ENG-037, decidir entre: (a) consolidar la deuda de ENG-032/033/034/035-dominio en commits coherentes, o (b) continuar con la siguiente historia funcional (ENG-038 — Learning Record Store interno, o ENG-035 dominio si se prioriza cerrar esa deuda primero).

---

## Notas del entorno

- **Rama:** `aligned-active-main` (trabajo directo sobre la rama activa, sin worktree — convención ya establecida en varias sesiones para este tipo de incremento).
- **Worktree:** existe `.worktrees/` con rama `eng-028`, no relacionado con el flujo actual.
- **Commit más reciente al cerrar esta sesión:** `3fb947e` (`docs(engineering): add progression rules implementation plan (ENG-037)`).
- **Archivos de referencia de patrones:** para código nuevo de Academic, usar como guía `EnrollmentProgress.php`, `EnrollmentProgressCalculator.php`, `CompleteLessonHandler.php`, `CourseLessonCatalog.php` y sus tests asociados (todos de ENG-036, ya en el patrón que ENG-037 debe seguir).
