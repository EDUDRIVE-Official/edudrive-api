# ENG-039 — Recomendaciones de aprendizaje (Diseño)

## 1. Objetivo

Exponer, por inscripción (`Enrollment`), recomendaciones de qué hacer a continuación en el curso: la próxima lección desbloqueada y no completada, las competencias con peor desempeño a través de todos los intentos de examen del curso, y los exámenes reprobados que todavía se pueden reintentar.

## 2. Alcance acordado con el usuario

**Incluido:**

- Recomendación de la próxima lección (desbloqueada, no completada).
- Refuerzo de competencias, agregado a través de todos los exámenes del curso (generaliza `TheoryStudyRecommendationService`, que hoy solo mira un intento a la vez).
- Recomendación de reintentar exámenes no aprobados que aún tienen intentos disponibles.

**Diferido explícitamente:**

- Integración con SIMUDRIVE (sistema externo, fuera de este repositorio).
- Rutas adaptativas (cambiar dinámicamente el orden/desbloqueo del currículo) — ya diferido desde ENG-037, sigue diferido aquí.
- Recomendaciones "según errores" a nivel de pregunta individual más allá de lo que ya aporta el desglose por competencia (evitar duplicar `question_ids` de evidencia que ya expone el refuerzo de competencias).

## 3. Diseño

Todo vive en `Modules\Academic`, siguiendo el mismo patrón que `EnrollmentProgressCalculator` / `CourseCurriculumUnlockCalculator` / `TheoryStudyRecommendationService` — no se crea un módulo nuevo.

### 3.1 Próxima lección

Reutiliza `CourseLessonCatalog::lessonIdsFor($course)` (orden curricular: módulo → unidad → lección) y `CourseCurriculumUnlockCalculator::statusFor($course, $progress)` (ya usado por `CompleteLessonHandler`/`GetEnrollmentCurriculumStatusHandler`). Recorre las lecciones en orden; la primera que no esté en `completedLessonIds()` **y** cuya unidad esté desbloqueada (`unlockStatus->isUnitUnlocked(unlockStatus->unitIdForLesson($lessonId))`) es la próxima lección. Si ninguna lección cumple ambas condiciones (curso completado, o todo lo restante bloqueado), `nextLessonId` es `null`. No se modifica ninguna clase de dominio existente.

### 3.2 Refuerzo de competencias

Mismo patrón de "sin N+1" que `EnrollmentProgressCalculator::evaluationsFor()`: se listan los exámenes del curso (`ExamRepository::all($courseId)`) una sola vez, y se cruzan en memoria contra `ExamAttemptRepository::all(userId: ..., status: Submitted)` filtrando por pertenencia al curso. Para cada examen, solo se considera el intento **enviado más reciente** (por `submittedAt()`) — un intento antiguo reprobado no debe ensombrecer una mejora posterior en el mismo examen. Se reutiliza exactamente la lógica de `TheoryStudyRecommendationService::build()` (breakdown por competencia con `percentage() < 100`, evidencia por `question_ids` desde `questionBreakdown()`) aplicada a cada intento más reciente, y se fusionan los resultados de todos los exámenes del curso en una sola lista, ordenada peor-primero, limitada a un máximo fijo (`self::MAX_WEAK_COMPETENCIES = 5`) para mantener la respuesta acotada. Se reutiliza la respuesta existente `StudyRecommendationResponse` (sin crear un DTO nuevo).

### 3.3 Exámenes para reintentar

Para cada examen del curso: se toma el intento enviado más reciente del usuario para ese examen. Se recomienda reintentar si `passed() === false` **y** `countCompletedFor($examId, $userId) < $exam->maxAttempts()` **y** no hay un intento activo (`findActiveFor($examId, $userId) === null`, para no recomendar "reintentar" un examen que ya está en curso). Nuevo DTO `RetryableExamResponse` (`exam_id`, `title`, `last_percentage`, `passing_score`, `attempts_used`, `max_attempts`).

### 3.4 Componentes nuevos

- `Modules\Academic\Application\Services\EnrollmentLearningRecommendationService` — combina las tres piezas anteriores. Constructor: `CourseRepository`, `CourseLessonCatalog`, `CourseCurriculumUnlockCalculator`, `ExamRepository`, `ExamAttemptRepository`. Método `build(Enrollment $enrollment, EnrollmentProgress $progress): LearningRecommendationsResponse`.
- `Modules\Academic\Application\Responses\LearningRecommendationsResponse` — `enrollmentId`, `nextLessonId` (nullable), `weakCompetencies` (`list<StudyRecommendationResponse>`), `retryableExams` (`list<RetryableExamResponse>`).
- `Modules\Academic\Application\Responses\RetryableExamResponse` (nuevo, ver 3.3).
- `Modules\Academic\Application\Queries\GetEnrollmentLearningRecommendationsQuery` (`enrollmentId`, `userId`, `canViewOthers`) + `GetEnrollmentLearningRecommendationsHandler` — mismo patrón de autorización que `GetEnrollmentProgressHandler` (dueño del enrollment o permiso ya existente `enrollments.view`; sin permiso nuevo).
- `EnrollmentProgressController::recommendations()` + ruta `GET /enrollments/{enrollmentId}/recommendations` bajo `auth:sanctum`, junto a `progress`/`curriculum` en el mismo controlador.
- Registro en `AcademicServiceProvider` junto a los demás query handlers de enrollment.

Errores públicos: solo se reutiliza `ENROLLMENT_NOT_FOUND` (404) — no hay validaciones nuevas de entrada (el endpoint no recibe payload).

## 4. Fuera de alcance / notas

- No se persiste nada nuevo: todo se deriva en memoria a partir de datos ya existentes (`Course`, `EnrollmentProgress`, `Exam`, `ExamAttempt`), igual que `CourseCurriculumUnlockCalculator`.
- No se toca `TheoryStudyRecommendationService` — se duplica su lógica de breakdown por competencia a nivel de un único intento dentro del nuevo servicio (extraerla a un helper compartido sería una mejora futura opcional, no bloqueante para este alcance).
