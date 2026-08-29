# Política de respaldos, RPO y RTO

Documento operativo derivado de ENG-084 (Backups y recuperación). Ver
`docs/plans/2026-08-29-backups-recuperacion-eng084-design.md` para el
diseño técnico completo.

## Qué se respalda

- **PostgreSQL**: la base de datos completa, vía `pg_dump -Fc` (formato
  custom comprimido). Comando: `php artisan backup:database`.
- **MinIO / almacenamiento de archivos**: no se respalda a un destino
  aparte en este alcance. En su lugar, el bucket tiene **versionado de
  objetos habilitado** (`php artisan files:ensure-bucket`, idempotente),
  que protege contra sobreescritura o borrado accidental de un archivo ya
  subido — cada versión anterior de un objeto permanece recuperable dentro
  del mismo bucket.

## Dónde se guardan los respaldos

Cada respaldo de PostgreSQL se sube al mismo bucket S3/MinIO ya configurado
para archivos de la aplicación (`config('filesystems.disks.s3')`), bajo el
prefijo `backups/postgres/{fecha}_{hora}.dump` — no requiere credenciales ni
infraestructura de almacenamiento adicional.

## Frecuencia

`php artisan backup:database` corre **diariamente** vía el scheduler real
activado en ENG-082 (`Schedule::command('backup:database')->daily()`,
ejecutado por el servicio `scheduler` de `compose.yaml`).

## RPO (Recovery Point Objective)

**24 horas.** Con un respaldo diario, en el peor caso se pierde el trabajo
de las últimas 24 horas antes de un incidente. Reducir el RPO requeriría
aumentar la frecuencia del respaldo (ej. cada 6 horas) — no forma parte de
este alcance; ver "Fuera de alcance" en el documento de diseño.

## RTO (Recovery Time Objective)

Medido en una restauración manual real contra la base de datos de
desarrollo (backup real generado con `pg_dump`, subido a MinIO real,
descargado y restaurado con `pg_restore` real): **~10.5 segundos** para el
volumen de datos actual del entorno de desarrollo. El RTO en un entorno de
producción con más datos sería proporcionalmente mayor — esta cifra es una
referencia de partida con el volumen de datos de hoy, no una garantía
contractual a futuro.

**Nota importante sobre el comportamiento de `pg_restore --clean --if-exists`**:
solo elimina y recrea los objetos que existen dentro del propio respaldo —
no purga tablas u objetos creados en la base de datos *después* de que se
generó ese respaldo. Confirmado en la validación manual: una tabla creada
después del backup permaneció intacta tras la restauración. Para una
recuperación total ante desastre (base de datos nueva y vacía, ej. un
servidor Postgres reemplazado), esto es irrelevante. Para restaurar sobre
una base de datos que sigue viva con cambios posteriores al respaldo, debe
asumirse que solo se repone el contenido que existía al momento del
respaldo — cualquier objeto añadido después permanece.

## Cómo restaurar

1. Ubicar el respaldo deseado (los objetos en `backups/postgres/` están
   nombrados por fecha y hora, `AAAA-MM-DD_HHMMSS.dump`).
2. Ejecutar `php artisan backup:restore backups/postgres/{archivo}.dump`.
3. Confirmar la operación cuando se solicite (o pasar `--force` para
   omitir la confirmación en un script no interactivo).
4. `pg_restore --clean --if-exists` reemplaza el esquema existente con el
   contenido del respaldo — **es una operación destructiva**, úsese solo
   quien tenga certeza de querer reemplazar el estado actual.

## Fuera de alcance de esta historia

- Réplica geográfica o destino secundario para los respaldos.
- Cifrado de los archivos de respaldo antes de subirlos.
- Rotación/retención automática de respaldos antiguos.
- Alerta automática (Slack, ya activado en ENG-083) si el respaldo
  programado falla.
