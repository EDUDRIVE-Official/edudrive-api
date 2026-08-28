# ENG-062 — Exportaciones: alcance acordado

Primera historia de la Fase 13 (o la que corresponda) que, a diferencia de ENG-061, no especifica en el roadmap QUÉ datos se exportan — solo los mecanismos (CSV, XLSX, PDF, exportaciones asíncronas, control de acceso, auditoría). Se investigó el estado real antes de proponer alcance.

## Estado previo encontrado (investigación, no una decisión del usuario)

- No existe ninguna consulta de listado con paginación hoy (`GetAuditLogsQuery`, `ListCoursesQuery`, `ListEnrollmentsQuery`, etc. todas usan `all()` sin límites).
- `league/csv` (instalada en ENG-061 para leer CSV de importación) también sabe **escribir** CSV (`League\Csv\Writer`), pero nada en el backend lo usa todavía.
- No existe ninguna librería de XLSX (`phpoffice/phpspreadsheet`, `maatwebsite/excel`) ni de PDF (`barryvdh/laravel-dompdf`, etc.) en `composer.json`.
- No existe ningún `ShouldQueue` job en todo el backend — sería el primero. `QUEUE_CONNECTION=database` y la tabla `jobs` ya existen, pero `compose.yaml` no tiene un worker de cola corriendo.
- `Modules\Audit` ya expone un servicio genérico y reutilizable, `AuditLogger::log(AuditEntry)`, usado hoy solo desde `Modules\Identity` (login/logout). No es específico de un módulo — cualquier módulo puede inyectarlo.
- `Modules\FileStorage` (ENG-060) ya expone `Application\Contracts\FileStorage` (`store`/`delete`/`temporaryDownloadUrl`, S3/MinIO) como primitiva de bajo nivel, independiente del agregado `StoredFile` (que además impone cuota por usuario).
- Permisos existentes con precedente de capacidad transversal (no atada a un recurso): `reports.view`, `system_operations.view` (ambos sin `.manage` correspondiente).

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Formato**: solo CSV en esta historia, reutilizando `league/csv` (`Writer`) ya instalada. XLSX y PDF diferidos por completo — cada uno implicaría agregar una librería nueva y construir su renderizado desde cero.
2. **Alcance de datos**: conjunto fijo y reducido de tres exportadores concretos — Auditoría, Cursos, Enrollments — reutilizando las consultas de listado ya existentes. Nada de un framework genérico de "exportar cualquier listado".
3. **Procesamiento**: síncrono, en la misma petición HTTP. Sin cola de trabajos (sería el primer `ShouldQueue` del backend, y no hay worker corriendo en `compose.yaml`).
4. **Control de acceso**: un permiso nuevo y transversal, `exports.view` (SuperAdmin + InstitutionalAdmin, mismo patrón que `reports.view`/`system_operations.view`), protege los tres endpoints de exportación — no se reutiliza el permiso `.view` de cada recurso, porque exportar todas las filas de una vez es un riesgo distinto a ver una lista paginada en pantalla.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Sin módulo nuevo**: cada exportador vive en el módulo dueño de los datos que exporta (`Modules\Admin` para Auditoría, `Modules\Academic` para Cursos y Enrollments) — mismo criterio que ENG-061 (cada bulk-import vive en el módulo dueño del agregado que crea).
- **`Modules\Foundation\Infrastructure\Export\CsvWriter`**: única excepción compartida — convertir `list<string>` (encabezados) + `list<list<string>>` (filas) a texto CSV es infraestructura pura, sin reglas de negocio, usada idénticamente por los tres exportadores. Duplicarla tres veces sería peor que centralizarla una vez en `Modules\Foundation`, que ya es el hogar de infraestructura compartida (bus de comandos/consultas). Análogo a `Modules\Foundation\Presentation\Http\Responses\ApiErrorResponse`.
- **`Modules\Foundation\Application\Responses\ExportResponse`**: DTO de respuesta compartido (`url`, `expires_at`, `row_count`, `format`) — no es un concepto de negocio de ningún módulo específico, es la forma genérica de "aquí está tu archivo exportado", igual de legítimo para compartir que `CsvWriter`.
- **No se usa el agregado `StoredFile` de `FileStorage`**: los exportadores llaman directamente a la interfaz de bajo nivel `Application\Contracts\FileStorage` (`store`/`temporaryDownloadUrl`), sin crear una fila `StoredFile` ni pasar por `UploadFileHandler`. Un archivo exportado es un artefacto generado por el sistema, no un archivo que un usuario sube — contarlo contra la cuota de almacenamiento de un usuario (pensada para adjuntos propios) no tiene sentido de producto. Dependencia entre módulos documentada (`Admin`/`Academic` → `FileStorage`), mismo criterio que `FileStorage` → `Admin` en ENG-060.
- **Ruta de almacenamiento `exports/{recurso}/{uuid}.csv`**, URL temporal de 15 minutos (mismo valor que `GetFileDownloadUrlHandler` en `FileStorage`).
- **Auditoría de cada exportación**: cada handler llama a `Modules\Audit\Application\Services\AuditLogger::log()` tras generar el archivo (`action` = `export.audit_logs`/`export.courses`/`export.enrollments`, `metadata` = `{row_count, format}`), reutilizando el servicio genérico ya existente — sin entidad/id propios porque es una exportación masiva, no la acción sobre un recurso puntual.
- **Sin filtros ni paginación en esta historia**: cada exportador exporta el conjunto completo que su consulta de listado ya devuelve hoy (sin límites) — introducir filtros de exportación sería alcance nuevo no pedido por el roadmap.

## Incluye (del roadmap)

- CSV.
- Control de acceso.
- Auditoría.

## Diferido explícitamente

- XLSX y PDF (requieren librerías nuevas y renderizado propio).
- Exportaciones asíncronas / cola de trabajos.
- Framework genérico de exportación de cualquier listado.
- Paginación o filtros sobre los datos exportados.
- Exportadores para Preguntas, Intentos de examen u otros listados (quedan para historias futuras que sigan el mismo patrón).
- Persistencia de un historial de exportaciones como agregado propio (más allá de la entrada de auditoría).
