# ENG-084 — Backups y recuperación: diseño

**Fase:** 17 — Plataforma y operación avanzada
**Alcance acordado:** reducido (recomendado, elegido por el usuario).

## Contexto y hallazgos de la investigación

El roadmap solo trae seis viñetas sueltas sin documento de diseño: PostgreSQL,
MinIO, Configuración, Restauración, Pruebas de recuperación, RPO y RTO. A
diferencia de ENG-081/082/083, la investigación (un agente en background)
confirmó que **no existe absolutamente nada construido**: sin paquete de
backup, sin cliente `pg_dump`/`pg_restore` en el `Dockerfile`, sin versionado
en el bucket de MinIO, sin política de RPO/RTO documentada en ningún lugar
del repo.

**Restricción real encontrada durante el diseño**: la suite de Pest corre con
`DB_CONNECTION=sqlite`/`DB_DATABASE=:memory:` (`phpunit.xml`), no contra
Postgres real. Esto significa que un test automatizado no puede ejecutar
`pg_dump`/`pg_restore` reales dentro de la suite — son herramientas
específicas de Postgres. Esto obliga a separar la lógica de "qué comando
ejecutar" (testeable sin Postgres real) de "ejecutarlo" (solo verificable
manualmente contra la base de datos real de desarrollo, vía Docker).

## Decisiones de diseño

### A. Nuevo módulo `Modules\Backup`

Es una operación de infraestructura pura, sin invariantes de negocio ni
agregados — no seguirá la estructura DDD completa (sin capa Domain con
aggregates). Solo `Application` (puertos), `Infrastructure` (implementación
real) y `Presentation` (comandos Artisan).

- `Application\Services\DatabaseDumper` (puerto): `dump(string $localPath): void`.
- `Application\Services\DatabaseRestorer` (puerto): `restore(string $localPath): void`.
- `Infrastructure\Services\PgDumpDatabaseDumper implements DatabaseDumper`:
  ejecuta `pg_dump -Fc` (formato custom comprimido, permite restore selectivo)
  vía `Symfony\Component\Process\Process`, leyendo credenciales de
  `config('database.connections.pgsql')`. Expone `commandLine(string
  $localPath): array` como método público separado — testeable sin ejecutar
  el proceso real.
- `Infrastructure\Services\PgRestoreDatabaseRestorer implements DatabaseRestorer`:
  análogo, ejecuta `pg_restore --clean --if-exists`.
- `docker/php/Dockerfile` gana el paquete `postgresql-client` (para tener
  `pg_dump`/`pg_restore`/`psql` disponibles en `app`/`queue-worker`/`scheduler`
  — las tres imágenes comparten el mismo `Dockerfile`).

### B. Comandos Artisan

- `backup:database` (`BackupDatabaseCommand`): genera el dump a un archivo
  temporal vía `DatabaseDumper`, lo sube a `FileStorage` con la ruta
  `backups/postgres/{timestamp}.dump` (mismo bucket S3/MinIO ya configurado,
  bajo un prefijo dedicado — no requiere credenciales ni bucket nuevos),
  borra el temporal. Programado diario vía `Schedule::command('backup:database')->daily()`
  (scheduler ya activado en ENG-082).
- `backup:restore {path}` (`RestoreDatabaseCommand`): descarga el archivo de
  `FileStorage` a un temporal vía un método nuevo `FileStorage::readToLocalFile()`,
  ejecuta `DatabaseRestorer`, borra el temporal. **Exige confirmación
  explícita** (`$this->confirm(...)`, salvo `--force`) por ser destructivo
  (`pg_restore --clean` borra el esquema existente antes de restaurar).

### C. `FileStorage` gana `readToLocalFile()`

La interfaz `Modules\FileStorage\Application\Contracts\FileStorage` gana
`readToLocalFile(string $storagePath, string $localTmpPath): void` —
necesario para descargar el dump antes de restaurarlo (no existía ninguna
forma de leer contenido de vuelta, solo `store`/`delete`/`temporaryDownloadUrl`).
Los 6 implementadores existentes (`S3FileStorage` real + 5 fakes de test de
ENG-081/082/083) ganan el método.

### D. Versionado de objetos en MinIO

`EnsureFileBucketExists` (`files:ensure-bucket`, ya existente) gana una
llamada a `putBucketVersioning(['Status' => 'Enabled'])` tras confirmar que
el bucket existe (recién creado o preexistente) — protege contra
sobreescritura/borrado accidental de un backup o archivo ya subido, sin
necesitar un destino secundario ni infraestructura nueva.

### E. Documentación de RPO/RTO

Nuevo documento `docs/operaciones/backups-rpo-rto.md` con la política real
derivada de lo construido: **RPO = 24 horas** (backup diario programado),
**RTO** estimado a partir del tiempo real medido al ejecutar
`backup:restore` manualmente contra la base de datos de desarrollo (ver
validación).

### F. Prueba de recuperación

**No se puede automatizar dentro de la suite de Pest** (usa SQLite en
memoria, sin `pg_dump`/`pg_restore` reales disponibles ni aplicables). Se
resuelve en dos niveles:

- **Automatizado** (Pest, sin Postgres real): tests unitarios de
  `PgDumpDatabaseDumper`/`PgRestoreDatabaseRestorer` verificando el array de
  argumentos del proceso (`commandLine()`), y tests de los comandos
  `BackupDatabaseCommand`/`RestoreDatabaseCommand` con un
  `DatabaseDumper`/`DatabaseRestorer` fake (escribe/lee contenido dummy) más
  un `FileStorage` fake — verifican toda la orquestación (ruta con
  timestamp, subida/descarga, limpieza de temporales, confirmación
  requerida) sin depender de Postgres real.
- **Manual, real, end-to-end** (Docker, contra la base de datos de
  desarrollo real): ejecutar `backup:database`, verificar el dump subido,
  modificar/borrar una fila real, ejecutar `backup:restore --force`,
  confirmar que los datos originales volvieron. Se documenta el resultado y
  el tiempo real medido (para el RTO del punto E) en el cierre de la
  historia (`ENG-LOG.md`), no como test automatizado.

## Fuera de alcance (documentado explícitamente)

- Destino secundario/réplica geográfica de los backups.
- Cifrado de los dumps antes de subirlos.
- Rotación/retención automática de backups antiguos (`backup:cleanup`).
- Alerta por Slack si un backup programado falla (reutilizaría el canal ya
  activado en ENG-083, pero es una feature adicional, no parte del mínimo
  verificable).

## Plan de verificación

Pint, PHPStan (`--memory-limit=512M`) sobre los módulos tocados y luego
sobre el repo completo. Validación manual real (backup+restore contra
Postgres de desarrollo) documentada explícitamente antes del cierre, dado
que no es viable como test automatizado por la razón explicada en el
punto F.
