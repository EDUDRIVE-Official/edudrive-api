# ENG-061 — Importaciones masivas: alcance acordado

Primera historia de la Fase 13 (o la que corresponda tras Fase 12) que no introduce un módulo nuevo: extiende `Modules\Identity` (usuarios/estudiantes) y `Modules\Academic` (cursos, preguntas) con un mecanismo de importación masiva por archivo CSV, mismo criterio arquitectónico que `CreateBulkEnrollmentsHandler` ya existente en `Modules\Academic` — el handler de lote llama directamente al handler de creación individual por cada fila y acumula un reporte `total`/`created`/`failed`/`results[]`, sin bus de comandos ni cola de trabajos.

## Estado previo encontrado (investigación, no una decisión del usuario)

- **Usuarios**: `RegisterUserUseCase` (`Modules\Identity\Application\UseCases`) ya crea un usuario individual (`name`, `email`, `password`). Autoservicio público, sin permiso, vía `POST /api/v1/auth/register`.
- **Estudiantes**: no es un concepto propio. Es un `User` con el rol `Student` asignado vía `Modules\Authorization\Application\Commands\AssignRoleCommand` → `AssignRoleHandler` (`userId`, `role`, `organizationId`).
- **Grupos**: no existe en absoluto en el backend — ni agregado, ni tabla, ni concepto equivalente (cohortes/secciones). Búsqueda exhaustiva sin resultados relevantes.
- **Cursos**: `CreateCourseHandler` (`Modules\Academic`) ya crea un curso individual (`code`, `title`, `description?`, `objectives?`, `prerequisites?`, `modality?`, `durationHours?`), validando código único.
- **Preguntas**: `CreateQuestionHandler` (`Modules\Academic`) ya crea una pregunta individual, pero requiere un `competencyId` existente y una forma de `response`/`options[]` con estructura anidada (dependiente de `QuestionType`).
- **Sin librería CSV/Excel**: `composer.json` no tiene `league/csv` ni `maatwebsite/excel` ni ninguna otra.
- **Precedente de lote**: `CreateBulkEnrollmentsCommand`/`Handler`/`BulkEnrollmentResponse` ya en `Modules\Academic` — construye el handler individual directamente (sin bus), itera, acumula `total`/`created`/`failed`/`results[]` con éxito parcial por fila.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Grupos**: se difiere por completo. No existe ningún concepto base y construirlo de cero (agregado, persistencia, CQRS, API) es una historia en sí misma, no una importación hacia algo existente.
2. **Usuarios + Estudiantes**: un solo mecanismo de importación. Cada fila incluye una columna `role` opcional; si se omite, se asigna `Student` por defecto (cubre el caso típico de importar un roster de estudiantes); si se especifica, se asigna ese rol en su lugar (cubre importar personal docente/administrativo bajo el mismo mecanismo). Reutiliza `RegisterUserUseCase` + `AssignRoleHandler` por fila.
3. **Validación previa**: integrada en la misma operación, sin modo "solo validar" separado. Cada fila se valida y, si es válida, se crea — éxito parcial por fila, mismo patrón que `BulkEnrollmentResponse`. El reporte de resultados (creados/fallidos con motivo) cumple el propósito de "validación previa" del roadmap sin un endpoint adicional ni estado intermedio que mantener.
4. **Procesamiento**: sincrónico, en la misma petición HTTP. Sin colas ni jobs en segundo plano. Adecuado para lotes moderados; no se opera con miles de filas ni se ofrece un límite configurable.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Sin módulo nuevo**: cada importación vive en el módulo dueño del agregado que crea (Identity para usuarios, Academic para cursos y preguntas), igual que `CreateBulkEnrollmentsHandler` vive en Academic junto a `CreateEnrollmentHandler`, no en un módulo "Bulk" centralizado.
- **`league/csv` como librería de parseo**: ligera, sin las dependencias pesadas de PhpSpreadsheet que trae `maatwebsite/excel`; suficiente para el formato CSV acordado (sin soporte Excel/XLSX en esta historia).
- **El parseo de CSV vive en la capa HTTP, no en Application**: el controlador convierte el archivo subido en un `list<array<string, string>>` (filas ya asociativas por encabezado) antes de construir el comando de lote — igual que `UploadFileCommand` recibe `localTmpPath` en vez de un `UploadedFile`, el dominio/aplicación no conoce la librería de parseo.
- **Límite de 500 filas por archivo** (validado en el `FormRequest`, mismo lugar que el límite de 20 MB de ENG-060): evita que una petición síncrona sin cola se cuelgue con un archivo desproporcionado.
- **Autorización reutilizada, sin permisos nuevos**: importar usuarios requiere `users.manage` (a diferencia del registro individual, que es autoservicio público — importar en lote es inequívocamente una acción administrativa); importar cursos requiere `courses.manage`; importar preguntas requiere `questions.manage`. Mismos permisos que ya protegen la creación individual de cada uno.
- **Preguntas — columnas `response`/`options`/`media`/`license_categories` como celdas JSON**: estas cuatro columnas del `Question` son estructuras anidadas dependientes del tipo de pregunta (`QuestionType`); una fila CSV es intrínsecamente plana. En vez de inventar un DSL propio con delimitadores anidados, cada una de esas columnas contiene una cadena JSON válida en la celda (p. ej. `response` = `{"correct_option_ref":"a"}`, `options` = `[{"refId":"a","label":"Sí"}]`), decodificada con `json_decode` antes de construir `CreateQuestionCommand`. Es una técnica conocida para CSV con datos anidados y evita construir un parser de mini-lenguaje para esta historia.
- **Preguntas — `competency_code` en vez de `competency_id`**: la columna de referencia a la competencia usa el código de negocio (`CompetencyCode`, ya existe `CompetencyRepository::findByCode()`) en vez del UUID interno, porque un UUID no es razonable de escribir a mano en una hoja de cálculo institucional.
- **Reporte de error por fila usa `DomainException::errorCode()`**: cada handler de lote captura `DomainException` (código de error propio, p. ej. `EMAIL_ALREADY_EXISTS`/`COURSE_CODE_ALREADY_EXISTS`) y, como red de seguridad, cualquier `Throwable` no esperado (p. ej. un rol inválido vía `Role::from()`) se reporta como `IMPORT_ROW_INVALID` — nunca se detiene el lote completo por una fila inválida.
- **Numeración de fila 1-indexada por línea de datos** (sin contar el encabezado) en cada resultado, para que el usuario pueda ubicar la fila exacta en su archivo original.
- **`Modules\FileStorage` (ENG-060) no se reutiliza**: el archivo de importación se procesa y se descarta en la misma petición; no hay necesidad de conservarlo, y acoplar esta historia a `FileStorage` sin un caso de uso real (trazabilidad futura) sería una dependencia prematura.

## Incluye (del roadmap)

- Usuarios (unificado con Estudiantes).
- Cursos.
- Preguntas.
- Validación previa (integrada, ver arriba).
- Reporte de errores (por fila, en la misma respuesta).

## Diferido explícitamente

- Grupos (no existe ningún concepto base; historia propia futura).
- Soporte Excel/XLSX (solo CSV).
- Procesamiento asíncrono / cola de trabajos / archivos grandes (miles de filas).
- Modo "solo validar sin crear" como paso separado.
- Persistencia del archivo de origen subido (vía `FileStorage` o cualquier otro medio).
- Plantillas de importación descargables, mapeo de columnas configurable.
