# ENG-033 — Motor de calificación (design)

Fecha: 2026-08-12
Historia: ENG-033 — Motor de calificación (Fase 6 — Evaluaciones)
Estado: Diseño aprobado

## 1. Objetivo

Implementar el motor de calificación sobre `ExamAttempt` para reemplazar el cálculo básico actual por un grading configurable y extensible. El incremento debe cubrir score final, porcentaje, aprobación, resultados parciales por pregunta, agregación por competencia y reglas configurables, dejando a `ENG-034` la experiencia específica del examen teórico de conducción.

## 2. Decisión de arquitectura

Se aprueba el enfoque **B**:

- `ExamAttempt` mantiene la responsabilidad del ciclo de vida del intento (`start`, `answer`, `submit`, `cancel`).
- La lógica de calificación se extrae a un servicio de dominio dedicado, por ejemplo `ExamAttemptGrader`.
- El grader recibe snapshot + respuestas + política de grading y devuelve un `GradingResult` inmutable.
- `ExamAttempt` persiste el resultado de grading y conserva la fuente de verdad del intento.

### Razones

- Evita que `ExamAttempt` crezca mezclando snapshot, ejecución y reglas avanzadas de grading.
- Facilita extender penalizaciones, parciales y análisis por competencia sin acoplar toda la lógica al agregado.
- Prepara un punto reutilizable para `ENG-034`, que necesitará consumir grading detallado sin redefinir las reglas base.

## 3. Alcance funcional aprobado

La primera versión de `ENG-033` entra con alcance **completo ahora**:

- score total alcanzado
- total de puntos posibles
- porcentaje final
- `passed`
- resultado por pregunta
- breakdown por competencia
- reglas configurables de grading
- soporte para parciales y penalizaciones cuando el tipo de respuesta lo permita

No incluye todavía la experiencia específica del examen teórico de conducción, categorías de licencia, banco oficial o recomendaciones de estudio; eso sigue diferido a `ENG-034`.

## 4. Diseño de dominio

### 4.1 Agregado `ExamAttempt`

`ExamAttempt` conserva:

- identidad, usuario, estado, snapshot del examen
- preguntas snapshot (`AttemptQuestion`)
- score/total_points/percentage/passed

Se amplía para almacenar además el resultado detallado del grading.

### 4.2 `AttemptQuestion`

El snapshot de cada pregunta debe enriquecerse con la información mínima necesaria para grading posterior sin depender del banco vivo:

- `competency_id`
- `question_type`
- `points`
- `correct_response`
- `user_response`
- `is_correct`
- datos opcionales de partial scoring / penalties derivados del tipo

La regla es que el intento pueda calificarse usando solo su propio snapshot.

### 4.3 Servicio de dominio `ExamAttemptGrader`

Servicio puro de dominio con firma conceptual:

```php
grade(ExamAttempt $attempt, GradingPolicy $policy): GradingResult
```

Responsabilidades:

- evaluar cada `AttemptQuestion`
- determinar puntos logrados por pregunta
- calcular score total y porcentaje
- calcular `passed`
- agrupar resultados por competencia
- devolver breakdown estructurado y serializable

### 4.4 `GradingPolicy`

VO o entidad inmutable de configuración del grading. Debe expresar al menos:

- `allowPartialCredit`
- `applyPenalties`
- reglas por tipo de pregunta cuando apliquen
- estrategia de agregación del score final

La política puede inicializarse desde defaults de dominio en esta primera versión y luego abrirse a configuración persistida si el roadmap lo requiere.

### 4.5 `GradingResult`

Objeto de resultado inmutable con al menos:

- `score`
- `totalPoints`
- `percentage`
- `passed`
- `questionBreakdown`
- `competencyBreakdown`

Cada item de `questionBreakdown` debe permitir exponer:

- posición
- tipo
- puntos posibles
- puntos logrados
- `is_correct`
- explicación breve del grading si aplica

Cada item de `competencyBreakdown` debe permitir exponer:

- `competency_id`
- puntos posibles
- puntos logrados
- porcentaje

## 5. Reglas de grading

### 5.1 Base común

- score final = suma de puntos logrados por pregunta
- total_points = suma de puntos posibles del snapshot
- percentage = `(score / total_points) * 100`, redondeado de forma consistente con la convención del módulo
- `passed` = `percentage >= passing_score`

### 5.2 Tipos de respuesta

- **single_choice / true_false**: todo-o-nada
- **multi_select**: configurable entre todo-o-nada y parcial; para `ENG-033` se aprueba soporte de parcial vía intersección correcta menos selección inválida si `applyPenalties` está activo
- **matching**: parcial por par correcto
- **ordering**: parcial por posición correcta
- **situational**: reutiliza la regla del subtipo subyacente o queda explícitamente todo-o-nada si aún no está modelado más fino

### 5.3 Penalizaciones

Se soportan en la política, pero deben implementarse de forma explícita y testeada.

Regla de diseño:

- nunca producir score negativo por pregunta
- nunca producir score total negativo
- el breakdown debe dejar claro cuándo hubo penalización aplicada

## 6. Persistencia

Se recomienda ampliar `academic_exam_attempts` con JSONs, no con tablas nuevas en esta iteración.

Campos nuevos propuestos:

- `grading_breakdown` JSON/JSONB
- `competency_results` JSON/JSONB

Razones:

- mantiene el incremento acotado
- evita una explosión de tablas para datos derivados
- suficiente para lectura API y debugging

El snapshot por pregunta ya existe en `academic_exam_attempt_questions`; ese nivel sigue siendo la fuente base para rehidratar el agregado. Los JSON del intento funcionan como materialización del resultado final.

## 7. Aplicación

### 7.1 Handlers

- `SubmitExamAttemptHandler` deja de depender del grading básico inline
- resuelve una `GradingPolicy`
- invoca `ExamAttemptGrader`
- aplica el `GradingResult` al agregado
- persiste el intento actualizado

### 7.2 Responses

`ExamAttemptResponse` se amplía para incluir:

- `grading_breakdown`
- `competency_results`

La exposición de feedback detallado sigue sujeta a `feedback_mode` y permisos del usuario.

## 8. HTTP / Presentación

No se requieren nuevos endpoints en `ENG-033`.

Cambios esperados:

- `GET /exam-attempts/{attemptId}` puede incluir breakdown detallado cuando el intento ya fue calificado
- `POST /exam-attempts/{attemptId}/submit` devuelve el grading completo

## 9. Testing

### Unit

- tests dedicados para `ExamAttemptGrader`
- ampliación de `ExamAttemptTest` para aplicar grading completo
- casos por tipo de pregunta y penalización

### Application

- `SubmitExamAttemptHandlerTest` debe verificar que usa el grader y persiste el breakdown

### Integration

- `EloquentExamAttemptRepositoryTest` debe validar persistencia/rehidratación de `grading_breakdown` y `competency_results`

### Feature

- `ExamAttemptTest` debe verificar que el submit devuelve breakdown ampliado
- `show` debe devolver u ocultar feedback detallado según permiso/feedback_mode

### Validación final esperada

- Pint
- PHPStan nivel 8 sin errores
- suites focalizadas de Academic
- migración aplicada y visible en `migrate:status`

## 10. Riesgos y decisiones

- **Snapshot enriquecido:** si `competency_id` no se guarda dentro de `AttemptQuestion`, el grading dependería del banco vivo; eso se rechaza.
- **Parciales y penalizaciones:** deben entrar detrás de reglas explícitas en `GradingPolicy`; no se permiten heurísticas implícitas.
- **JSON de resultados:** aceptado para esta iteración por velocidad y simplicidad; si luego se requieren reportes analíticos pesados, podrá refactorizarse a tablas especializadas.
- **Compatibilidad con ENG-032:** el grading básico actual debe migrarse sin romper handlers, repo ni endpoints ya verificados.
- **Preparación para ENG-034:** este diseño deja el motor listo para que el examen teórico solo agregue reglas y experiencia específicas, no otro sistema paralelo de calificación.
