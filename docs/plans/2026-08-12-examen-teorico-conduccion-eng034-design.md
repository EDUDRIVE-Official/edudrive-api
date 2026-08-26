# ENG-034 — Examen teorico de conduccion (design)

Fecha: 2026-08-12
Historia: ENG-034 — Examen teorico de conduccion (Fase 6 — Evaluaciones)
Estado: Diseno aprobado

## 1. Objetivo

Construir la primera experiencia especializada de examen teorico de conduccion sobre la infraestructura ya creada en `Modules\Academic`, reutilizando `Exam`, `ExamAttempt` y `ExamAttemptGrader`. El incremento debe cubrir simulacion de examen por categoria de licencia, restriccion a banco oficial autorizado, reglas configurables de grading, historial teorico y recomendaciones basicas de estudio.

## 2. Decision de arquitectura

Se aprueba un enfoque de **especializacion sobre Academic**, no un subdominio nuevo.

- `Exam` sigue siendo la plantilla base del examen.
- `ExamAttempt` sigue siendo la ejecucion del intento.
- `ExamAttemptGrader` sigue siendo el motor unico de calificacion.
- `ENG-034` agrega metadatos, validaciones y endpoints especializados para la experiencia teorica, sin duplicar agregados ni abrir un sistema paralelo.

### Razones

- `ENG-031`, `ENG-032` y `ENG-033` ya cubren definicion, ejecucion y grading de examenes; reimplementar eso en otro modulo seria trabajo duplicado.
- El roadmap ubica `Pasaporte Vial` en una fase posterior (`ENG-040+`), por lo que adelantar ahora un subdominio de conduccion mezclaria decisiones de producto distintas.
- La especializacion permite entregar valor funcional ahora y deja `Pasaporte Vial` libre para consumir evidencia mas adelante, en lugar de convertirse en prerequisito de la experiencia teorica.

## 3. Alcance funcional aprobado

La primera version de `ENG-034` entra con el siguiente alcance:

- configuracion de examen teorico por categoria de licencia
- simulacion de examen usando `ExamAttempt`
- restriccion a preguntas de banco oficial autorizado
- reglas configurables de grading por examen teorico
- historial de intentos teoricos por usuario y categoria
- recomendaciones basicas de estudio derivadas del breakdown ya calculado

Queda fuera en esta iteracion:

- frontend especifico del examen teorico
- perfiles normativos profundos por pais
- integracion con `Pasaporte Vial`
- `Learning Record Store`
- recomendaciones adaptativas avanzadas o personalizacion por IA

## 4. Modelo funcional

### 4.1 Preguntas del banco teorico

Las preguntas siguen viviendo en `Question`, pero se enriquecen con metadata de elegibilidad teorica:

- `source_kind`: origen de la pregunta (`official` o `custom`)
- `source_reference`: referencia textual opcional al banco o resolucion oficial
- `license_categories`: lista de categorias de licencia para las que la pregunta esta autorizada

Esta decision evita un segundo banco de preguntas. El examen teorico usa el banco existente, pero con restricciones de origen y categoria.

### 4.2 Examen teorico

`Exam` se amplia con metadatos que permiten distinguir una plantilla teorica de una plantilla academica general:

- `kind`: `standard` o `theory`
- `license_category`: categoria de licencia cuando `kind = theory`
- `allow_partial_credit`: define la politica de parciales usada al enviar el intento
- `apply_penalties`: define la politica de penalizaciones usada al enviar el intento

Los campos existentes (`duration_minutes`, `max_attempts`, `passing_score`, `shuffle_questions`, `feedback_mode`, lista de preguntas) se conservan y siguen siendo la base principal de configuracion.

### 4.3 Intento teorico

No se crea un agregado nuevo para intentos teoricos.

- Un intento teorico sigue siendo `ExamAttempt`.
- La condicion de "teorico" se determina por la definicion del `Exam` asociado.
- El historial teorico se obtiene filtrando intentos cuyos examenes estan marcados como `kind = theory`.

### 4.4 Recomendaciones de estudio

Las recomendaciones se derivan del `GradingResult` ya persistido:

- priorizan competencias con menor porcentaje o score
- pueden incluir preguntas falladas o parcialmente correctas como evidencia de soporte
- no se persisten en una tabla nueva en esta version; se calculan al responder `show`/`submit` o en un endpoint especializado de recomendaciones

## 5. Reglas de negocio

### 5.1 Banco oficial autorizado

Si un examen esta marcado como `kind = theory`:

- todas sus preguntas deben tener `source_kind = official`
- todas sus preguntas deben incluir la `license_category` del examen dentro de `license_categories`

Si alguna pregunta incumple, el examen debe rechazarse como invalido desde la capa de aplicacion con error publico explicito.

### 5.2 Categoria de licencia

La primera version usa una categoria controlada por codigo de texto normalizado. La capa de dominio no se acopla todavia a un catalogo nacional detallado; solo exige presencia y formato valido cuando el examen es teorico.

Esto permite iniciar con categorias como `A`, `B`, `C` y refinar el catalogo regional mas adelante sin reescribir toda la arquitectura.

### 5.3 Reglas configurables de grading

El `SubmitExamAttemptHandler` deja de construir una `GradingPolicy` fija para todos los examenes.

- examenes `standard` conservan defaults conservadores
- examenes `theory` construyen la politica usando `allow_partial_credit` y `apply_penalties`

La configuracion vive en el examen, no en el intento, pero el resultado final sigue materializado en `ExamAttempt` como hasta ahora.

### 5.4 Historial y visibilidad

- el historial teorico por defecto muestra los intentos del propio usuario
- roles con permiso ampliado (`exam_attempts.view`) pueden consultar terceros, conservando el patron actual
- las recomendaciones solo aparecen cuando el intento esta `submitted`
- si el intento queda `canceled` por timeout, no expone grading ni recomendaciones

## 6. API / Presentacion

Se aprueba una capa de endpoints especializados, manteniendo compatibilidad con la API ya existente.

### 6.1 Endpoints base que se reutilizan

- `PUT /api/v1/academic/exam-attempts/{attemptId}/questions/{position}`
- `POST /api/v1/academic/exam-attempts/{attemptId}/submit`
- `POST /api/v1/academic/exam-attempts/{attemptId}/cancel`
- `GET /api/v1/academic/exam-attempts/{attemptId}`

### 6.2 Endpoints especializados nuevos

- `GET /api/v1/academic/theory-exams`
- `GET /api/v1/academic/theory-exams/{examId}`
- `POST /api/v1/academic/theory-exams`
- `PUT /api/v1/academic/theory-exams/{examId}`
- `POST /api/v1/academic/theory-exams/{examId}/start`
- `GET /api/v1/academic/theory-attempts`

La capa especializada no duplica el flujo de respuesta/envio del intento; delega al sistema de intentos ya implementado.

### 6.3 Responses

- `QuestionResponse` puede exponer `source_kind`, `source_reference` y `license_categories`
- `ExamResponse` debe exponer `kind`, `license_category`, `allow_partial_credit` y `apply_penalties`
- `ExamAttemptResponse` debe poder incluir `study_recommendations` cuando el intento pertenece a un examen teorico enviado

## 7. Persistencia

Se aprueba ampliar las tablas existentes y evitar tablas nuevas de intentos o grading.

### 7.1 `academic_questions`

Agregar:

- `source_kind` string
- `source_reference` nullable string
- `license_categories` JSON/JSONB

### 7.2 `academic_exams`

Agregar:

- `kind` string con default `standard`
- `license_category` nullable string
- `allow_partial_credit` boolean default `false`
- `apply_penalties` boolean default `false`

### 7.3 Sin cambios estructurales

- `academic_exam_attempts` se reutiliza para historial teorico
- `academic_exam_attempt_questions` se reutiliza como snapshot del intento
- `grading_breakdown` y `competency_results` siguen siendo la fuente para recomendaciones

## 8. Permisos

En esta primera version se reutilizan los permisos actuales:

- `exams.view` / `exams.manage` para definir examenes teoricos
- acceso del propio usuario para iniciar y rendir simulaciones
- `exam_attempts.view` para historial ampliado o revision de terceros

No se introduce todavia un permiso nuevo exclusivo para examenes teoricos.

## 9. Testing

### Unit

- validacion de `Question` con metadata teorica
- validacion de `Exam` cuando `kind = theory`
- recomendaciones derivadas del breakdown

### Application

- handlers especializados para listar/crear/actualizar examenes teoricos
- inicio de simulacion teorica delegando a `ExamAttempt`
- construccion de `GradingPolicy` desde configuracion del examen

### Integration

- roundtrip Eloquent de metadata teorica en `Question` y `Exam`
- historial teorico filtrado por categoria

### Feature

- creacion y consulta de examen teorico
- rechazo de preguntas no oficiales o fuera de categoria
- inicio y submit de simulacion teorica
- historial teorico del estudiante
- recomendaciones en el detalle del intento enviado

## 10. Riesgos y decisiones

- **No crear modulo nuevo ahora:** aprobado. `Pasaporte Vial` queda diferido y no condiciona `ENG-034`.
- **Categorias por codigo controlado:** aceptado en esta iteracion para no bloquear el incremento por una taxonomia nacional completa.
- **Banco oficial modelado como metadata de pregunta:** aceptado por simplicidad; un catalogo normativo mas rico podra aparecer despues.
- **Recomendaciones sin persistencia nueva:** aceptado; se calculan desde grading materializado, suficiente para la primera version.
- **API especializada con nucleo compartido:** aprobada para equilibrar claridad de producto y reutilizacion tecnica.
