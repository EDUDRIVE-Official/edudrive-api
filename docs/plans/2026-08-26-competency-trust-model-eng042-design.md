# ENG-042 — Competency Trust Model (Diseño)

## 1. Objetivo

Calcular un `trust_score` (0-100) para el `RoadPassport` a partir de su evidencia acumulada (ENG-041), combinando fuente, recencia y consistencia. Es un cálculo derivado — no se persiste nada nuevo, se recalcula al vuelo cada vez que se consulta el pasaporte.

## 2. Alcance acordado con el usuario

**Incluido:** un `trust_score` global para todo el pasaporte (no por competencia — la evidencia actual es a nivel de curso/examen, no tiene desagregación por competencia todavía), combinando:

- **Fuente de evidencia**: peso fijo por `EvidenceType` (`exam_passed` pesa más que `course_completed`, un examen aprobado es una señal más fuerte que completar un curso).
- **Recencia de la evidencia**: decaimiento por antigüedad — evidencia reciente pesa su valor completo; evidencia vieja decae hacia un piso mínimo (**reglas de degradación temporal**).
- **Consistencia**: más piezas de evidencia independientes aumentan la confianza en el resultado, con retornos decrecientes (un multiplicador, no una suma lineal sin límite).

**Diferido explícitamente:** desagregación por competencia individual (requeriría extender `Evidence` con el desglose por competencia que ya calcula `ExamAttemptGrader` pero que hoy no se propaga al pasaporte); validez/expiración de evidencia individual (sigue diferida desde ENG-040 — toda evidencia es válida indefinidamente en este alcance); persistencia del score (se recalcula siempre al vuelo, no hay historial de trust score).

## 3. Diseño

### 3.1 Fórmula

Servicio de dominio puro `Modules\RoadPassport\Domain\Services\RoadPassportTrustCalculator`, sin dependencias de infraestructura (mismo espíritu que `CourseCurriculumUnlockCalculator` en Academic):

```
calculate(RoadPassport $passport, DateTimeImmutable $now): int
```

Por cada `Evidence` en `$passport->evidence()`:

1. **Peso base por fuente**: `exam_passed` = 15, `course_completed` = 10.
2. **Decaimiento por recencia** (`ageInDays = now - occurredAt`):
   - `ageInDays <= 90` → factor `1.0` (evidencia reciente, sin degradar).
   - `ageInDays >= 365` → factor `0.2` (piso mínimo, nunca llega a cero: la evidencia vieja sigue contando algo).
   - Entre 90 y 365 días: interpolación lineal entre `1.0` y `0.2`.
3. Peso ponderado de esa evidencia = peso base × factor de decaimiento.

Se suman los pesos ponderados de toda la evidencia (`rawTotal`). Se aplica el **multiplicador de consistencia**, creciente con la cantidad de evidencia pero acotado: `min(1.0, 0.5 + 0.1 × count(evidence))` — con una sola pieza de evidencia el multiplicador es `0.6`; con 5 o más piezas llega a `1.0` (no sigue creciendo más allá).

`trust_score = min(100, round(rawTotal × multiplicadorDeConsistencia))`. Sin evidencia, `trust_score = 0`.

### 3.2 Exposición

`RoadPassportResponse::fromRoadPassport()` recibe un `?DateTimeImmutable $now = null` opcional (por defecto `now()`) y calcula `trust_score` internamente instanciando `new RoadPassportTrustCalculator()` — es puro y sin dependencias, mismo patrón que `new ExamAttemptGrader` / `new TheoryStudyRecommendationService` como valores por defecto en otros handlers de este proyecto. No se inyecta por contenedor porque no lo necesita.

Se agrega el campo `trust_score` (int) a la respuesta existente — visible en `GET /road-passport/me` y `/{id}`, sin endpoint ni permiso nuevo.
