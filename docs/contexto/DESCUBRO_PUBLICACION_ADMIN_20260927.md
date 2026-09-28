# DESCUBRO — Publicación para revisión administrativa

Estado: publicada y verificada; disponible solo para administración general.
Destino: https://app.edudrive.vr506.com.
Versión: `descubro-review-20260927r1`.

## Alcance preparado

Incluye el recorrido, avance por cuenta, ilustraciones, resumen de habilidades, narración opcional en español, repaso y enlaces desde perfil/Pasaporte. La candidata añade `DESCUBRO_PRODUCTION_REVIEW_ENABLED`, desactivado por defecto. Al activarlo en producción, solo cuentas activas con rol global `super_admin` pueden acceder. Los estudiantes, docentes y administradores institucionales siguen bloqueados tanto en enlaces como en GET/POST directos. No se modifican sus roles ni se abre acceso a datos de otras cuentas.

La restricción es adicional a autenticación web y controles existentes. Local/testing y el interruptor de staging conservan su comportamiento. La etiqueta de la experiencia pasa a «Vista de revisión».

## Verificación completada

- Diez pruebas, 149 aserciones en contenedor aislado: acceso por ambiente/rol, cuentas activas/inactivas, inicio de sesión, lectura/escritura, recorrido y repaso. Formato PHP revisado.
- Se compararon los cinco archivos compartidos con la app publicada: coinciden con la base de referencia y el parche aplica sin conflictos.
- Entrega de veinte archivos de funcionamiento, sin incorporar íntegramente otros cambios locales pendientes.
- Imágenes candidatas construidas en el VPS a partir de las imágenes que ya funcionaban allí. Las rutas DESCUBRO se cargaron en un contenedor aislado; la imagen de nginx se verificó.

Imagen candidata de aplicación:
`sha256:5df8202d168a4c8c6ebd376f4f21162e375de52848cc83faf662b16e6e5f5402`

Imagen candidata de nginx:
`sha256:4daa072eea581b1a70117cf46fa37a393bdf5b30040217ed8b1f1447f6c69c52`

Las imágenes activas anteriores se conservaron mediante etiquetas específicas. El directorio de entrega es `/opt/edudrive/releases/descubro-review-20260927r1`. El código local quedó respaldado antes de la modificación en `tmp/descubro-publish/backup-20260927-181413`.

## Operación lista para ejecutar tras autorización

1. Transferir y verificar el paquete final con SHA-256 `1e9c3e2f7938fcce8af131bd5e16b43056065776f84b6b72736e113f207ece61`.
2. Volver a comprobar que las imágenes activas son las versiones comparadas.
3. Crear un respaldo PostgreSQL en el servidor, con acceso restringido, y verificar que el archivo es legible mediante `pg_restore --list`. Esto comprueba la estructura del archivo; no es un ensayo de restauración completa.
4. Aplicar únicamente `2026_09_25_000001_create_academic_descubro_progress_table.php`.
5. Activar las imágenes candidatas en app, worker, scheduler y nginx, con el interruptor de revisión administrativa.
6. Reconstruir las cachés y verificar salud HTTPS, redirección para visitantes anónimos, recurso de audio y recorrido en la sesión del administrador.

La actualización utiliza `/opt/edudrive/compose.descubro-review.yaml` además de los dos archivos Compose existentes. Las operaciones posteriores deberán conservar ese override hasta integrar formalmente la versión. El script normal de despliegue no conoce esta entrega y no debe usarse indiscriminadamente.

## Retorno a la versión anterior

El archivo de entrega `compose.rollback.yaml` apunta a las imágenes anteriores registradas y desactiva la revisión. El procedimiento de publicación intenta restaurarlas si falla después de iniciar el reemplazo. Conserva la nueva tabla y el respaldo; no elimina avances. No se ha probado una restauración completa de la base.

## Bloqueo de aprobación

La revisión automática rechazó la ejecución del despliegue porque la orden «continúa» no constituía autorización explícita para una mutación de producción con migración y reinicio de servicios. Se pidió al usuario autorizar ese alcance concreto. El comando rechazado no se ejecutó: no se generó todavía el respaldo, no se aplicó la migración y no se reemplazaron los servicios activos. La construcción previa de candidatas sí se completó; no equivale a publicación.

## Resolución y publicación completada

Después de la solicitud explícita que detalló respaldo, migración y reinicio, el usuario respondió «continua». Se ejecutó la operación indicada y finalizó correctamente. El estado pendiente descrito en la sección anterior corresponde al intento previo, ya resuelto.

- Paquete final verificado antes de ejecutarlo.
- Respaldo guardado únicamente en el servidor: `/opt/edudrive/releases/descubro-review-20260927r1/before-descubro.dump`, 465079 bytes, permisos 600. SHA-256: `dd7ea7184507a783398b82e75ed8a8e0d6989361e2c787d6cd748fbdb9c53892`. Se verificó la lectura de su catálogo mediante `pg_restore --list`.
- Migración específica aplicada, lote 2. No se ejecutaron seeders ni otras migraciones.
- App, worker, scheduler y nginx utilizan `descubro-review-20260927r1`. Nginx, PostgreSQL y Redis reportaron estado saludable; los cuatro servicios de aplicación quedaron en ejecución. No se reiniciaron los servicios de datos.
- El servidor conserva `APP_ENV=production`, `APP_DEBUG=false` y `DESCUBRO_PRODUCTION_REVIEW_ENABLED=true`.
- Salud HTTPS comprobada, visitantes anónimos redirigidos al ingreso y archivo de narración servido correctamente.
- En la sesión real del administrador se verificaron el acceso desde Mi perfil, pantalla inicial, repaso, inicio y detención de lectura y regreso al recorrido. Se dejó abierta la dirección pública de DESCUBRO y se guardó `DESCUBRO_publicado_20260927.png`.

La revisión del navegador no inició ni completó una práctica en la cuenta de producción. La persistencia y la restricción de otros roles se comprobaron en las pruebas aisladas; no se crearon cuentas ni datos ficticios en producción para repetirlas. No se copió el avance de localhost a la app pública.

## Operación posterior

Usar los tres archivos al gestionar los servicios de esta entrega:

```sh
cd /opt/edudrive
docker compose -p edudrive-prod -f compose.prod.yaml -f compose.bootstrap.yaml -f compose.descubro-review.yaml ps
```

Para una reversión deliberada del código, conservando la base y su nueva tabla:

```sh
cd /opt/edudrive
docker compose -p edudrive-prod -f compose.prod.yaml -f compose.bootstrap.yaml -f releases/descubro-review-20260927r1/compose.rollback.yaml up -d --no-deps --no-build --pull never --force-recreate app queue-worker scheduler nginx
docker exec edudrive-prod-app-1 php artisan view:clear
```

No ejecutar estos comandos de reversión como verificación. Las imágenes anteriores y el respaldo siguen conservados; no fue necesario volver atrás. El checkout del servidor y Git no se consolidaron en esta entrega: los archivos aplicados están documentados en la entrega y las imágenes. Antes de una siguiente publicación, integrar esta versión o mantener explícitamente su override para evitar regresar accidentalmente a la anterior.
