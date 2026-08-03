# ENG-027 — Módulos y unidades — Diseño

**Fecha:** 2026-08-03  
**Estado:** Aprobado  
**Ámbito:** Catálogo curricular regional de EduDrive

## Objetivo

Agregar a los cursos una estructura curricular jerárquica, ordenada y validada:

```text
Curso → Módulos ordenados → Unidades ordenadas
```

El incremento define metadatos y prerrequisitos curriculares. No incorpora todavía lecciones, contenido multimedia, progreso de estudiantes ni versionado.

## Decisiones principales

- Cada módulo pertenece exclusivamente a un curso.
- Cada unidad pertenece exclusivamente a un módulo.
- La jerarquía es fija en dos niveles; no existen árboles arbitrarios.
- `Course` es la raíz del agregado y controla el currículo completo.
- El currículo se consulta y reemplaza como una unidad atómica.
- Solo un curso en estado `draft` admite cambios estructurales.
- Un curso nuevo solo puede publicarse si tiene al menos un módulo y cada módulo tiene al menos una unidad.
- Los cursos publicados antes de ENG-027 pueden restaurarse y consultarse sin currículo para mantener compatibilidad.

## Modelo de dominio

`Course` incorporará una colección ordenada de `LearningModule`. Cada módulo contendrá una colección ordenada de `LearningUnit`.

### LearningModule

- UUID técnico global.
- Código normalizado, único dentro del curso.
- Título obligatorio.
- Descripción obligatoria.
- Objetivos de aprendizaje opcionales.
- Duración estimada opcional en minutos, siempre positiva.
- Posición consecutiva desde 1.
- Lista opcional de UUID de módulos prerrequisito.

### LearningUnit

- UUID técnico global.
- Código normalizado, único dentro del módulo.
- Título obligatorio.
- Descripción obligatoria.
- Objetivos de aprendizaje opcionales.
- Duración estimada opcional en minutos, siempre positiva.
- Posición consecutiva desde 1 dentro del módulo.
- Lista opcional de UUID de unidades prerrequisito.

### Prerrequisitos

- Un módulo solo puede requerir módulos anteriores del mismo curso.
- Una unidad solo puede requerir unidades anteriores en el orden curricular: unidades previas del mismo módulo o unidades de módulos anteriores.
- No se aceptan autorreferencias, referencias inexistentes, externas, futuras ni duplicadas.
- La restricción de referencias hacia atrás impide ciclos; el agregado también valida explícitamente la consistencia completa antes de mutar.

### Ciclo de vida

- `draft`: permite reemplazar el currículo completo.
- `published`: el currículo es inmutable.
- `archived`: el curso y su currículo son inmutables.
- La publicación de un curso nuevo exige al menos un módulo y una unidad por módulo, posiciones consecutivas y prerrequisitos válidos.
- La restauración admite cursos publicados legados sin currículo, pero estos no pueden modificarse estructuralmente.

`Course::replaceCurriculum()` construirá y validará la estructura candidata completa antes de asignarla. Un error nunca debe dejar una mutación parcial.

## Persistencia

Se agregarán cuatro tablas normalizadas:

- `academic_course_modules`
- `academic_course_units`
- `academic_module_prerequisites`
- `academic_unit_prerequisites`

Restricciones:

- Código de módulo único por curso.
- Posición de módulo única por curso.
- Código de unidad único por módulo.
- Posición de unidad única por módulo.
- Claves foráneas para curso, módulo, unidad y prerrequisitos.
- Duraciones positivas cuando estén presentes.
- Eliminación en cascada solo entre el curso y sus hijos curriculares.

`EloquentCourseRepository` reconstruirá el agregado con carga anticipada y orden explícito. El reemplazo eliminará relaciones obsoletas y persistirá módulos, unidades y prerrequisitos dentro de una sola transacción. Cualquier fallo revierte la estructura completa.

## API y aplicación

### Consulta

```http
GET /api/v1/academic/courses/{courseId}/curriculum
```

- Requiere `auth:sanctum` y `courses.view`.
- Devuelve el curso, estado y currículo normalizado en orden.

### Reemplazo atómico

```http
PUT /api/v1/academic/courses/{courseId}/curriculum
```

- Requiere `auth:sanctum` y `courses.manage`.
- Recibe la estructura completa.
- Los UUID de módulos y unidades son suministrados por el cliente para mantener identidades estables ante reordenamientos y futuras asociaciones con lecciones.
- Los prerrequisitos usan esos UUID y deben resolverse dentro del mismo payload.

Flujo:

1. El Form Request valida forma, tipos, UUID, longitudes y límites numéricos.
2. El handler carga el curso o devuelve un error público 404.
3. El agregado valida la estructura candidata completa.
4. Solo después de validar, reemplaza el currículo.
5. El repositorio guarda todo transaccionalmente.
6. La respuesta devuelve el currículo normalizado y ordenado.

El endpoint existente de publicación reutilizará las nuevas invariantes para cursos en borrador. No se agregarán endpoints granulares de módulo o unidad en este incremento.

## Errores públicos

Se expondrán errores de dominio/aplicación estables para, al menos:

- Curso inexistente.
- Currículo no modificable por estado.
- Currículo requerido para publicación.
- Módulo sin unidades.
- UUID o código duplicado.
- Posición inválida o no consecutiva.
- Duración inválida.
- Prerrequisito inexistente, externo, duplicado, propio o futuro.

Las validaciones de forma HTTP responderán con el contrato estándar de validación de Foundation. Las invariantes de dominio producirán respuestas 422 y los recursos inexistentes 404.

## Estrategia de pruebas

El incremento se implementará con TDD en cuatro niveles:

1. **Dominio:** creación/restauración, normalización, orden, duplicados, prerrequisitos, atomicidad, lifecycle y publicación.
2. **Aplicación:** consulta/reemplazo, errores públicos, carga del curso y ausencia de `save()` ante cualquier fallo.
3. **Persistencia:** ida y vuelta completa, orden, relaciones, transacción, restricciones y compatibilidad con cursos legados.
4. **HTTP:** autenticación, permisos, validación, reemplazo completo, consulta, publicación y rechazo de mutaciones posteriores.

Flujo de aceptación:

1. Crear un curso en borrador.
2. Guardar varios módulos y unidades ordenados.
3. Definir prerrequisitos válidos.
4. Rechazar referencias futuras, externas o duplicadas sin modificar el currículo previo.
5. Publicar únicamente con currículo completo.
6. Consultar la misma estructura en su orden original.
7. Rechazar cambios estructurales después de publicar.

## Fuera de alcance

- Lecciones, texto didáctico, imágenes, video, audio o contenido interactivo (ENG-028).
- Recursos descargables y accesibilidad de contenido (ENG-028).
- Borradores curriculares versionados, revisión, aprobación e historial (ENG-029).
- Inscripción, progreso y aplicación real de reglas de avance (ENG-035 a ENG-037).
- Reutilización de módulos o unidades entre cursos.
- Interfaz web para editar el currículo.
- Perfiles normativos o categorías legales por país.
