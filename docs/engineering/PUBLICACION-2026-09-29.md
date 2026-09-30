# Publicación de consolidación — 29 de septiembre de 2026

Autorización: el usuario solicitó publicar todos los cambios en la app en línea.
Versión activa: `consolidacion-20260929r1`, activada a las **14:00:41 UTC / 08:00:41 Costa Rica**.
URL: https://app.edudrive.vr506.com.

## Alcance y trazabilidad

Se construyeron imágenes nuevas desde el código local actual (base Git `3d69d2a` más cambios todavía sin commit), incluidos los ajustes de fechas, seeders y menú. No se hizo commit ni push y no se integró main. Los cambios documentales posteriores al empaquetado permanecen locales.

- App, worker y scheduler: `edudrive-api:consolidacion-20260929r1`, imagen `sha256:9cfc31fd8d389818193b802a60e8b9997e7c8fe7bd5f3b4be111bcc0eefa70a8`.
- Nginx: `edudrive-nginx:consolidacion-20260929r1`, imagen `sha256:af48146799d4fff4a845f706e57b0c7e6dea6eacf5b0f3b984c9d747b0d5283d`.
- Entrega: `/opt/edudrive/releases/consolidacion-20260929r1`.
- Paquete de código `source.tar.gz`: SHA256 `17fda5ec6f6d49f9ca79863867d078236305edc4d0d05dd867b9cb7959adb93a`.
- Complemento de traducciones, juego y estructura storage `runtime-extra.tar.gz`: SHA256 `b3cf6ede60eca894f68fd3818aef66ac511a7f8e6b529f7259046717d97fdb52`.
- Se conservaron `.env`, volúmenes, permisos, zona horaria y revisión DESCUBRO restringida. No hubo migraciones, seeders, cambios de contraseña ni correcciones masivas de datos.

## Respaldo y validación

Respaldo nuevo `before.dump` en el directorio de entrega, SHA256 `c7b7a0e812fa503ae64b2ea1ff74f6ff52bd7fc5cbddc80807f6c54b6ba40fb3`, acceso restringido. Restauración completa con `pg_restore --exit-on-error --no-owner --no-privileges` en PostgreSQL 17 aislado, sin puertos ni red externa: **91 tablas, 71 migraciones, 4 usuarios**. No certifica recuperación de roles/ACL ni del almacenamiento de objetos; esos servicios no fueron modificados.

En la copia, la imagen candidata conservó el valor de **86 fechas** al leer/serializar: 14 de usuarios y 72 de versiones de curso. No hubo candidatos a purga por inactividad (retención evaluada de 3 años). Matrículas, programas, certificados, sesiones, trabajos asíncronos, consumidores API, eventos, pasaportes y evaluaciones IA estaban vacíos. Esto comprueba que la nueva serialización no desplaza esos valores existentes; no demuestra que sus horas originales sean históricamente correctas. No se reinterpretó el histórico ni se convirtió date_of_birth.

Las cinco pantallas `/courses`, `/mi-perfil`, `/mi-pasaporte-vial`, `/organizations` y `/descubro/cruzar-acompanado` respondieron **200** mediante el kernel HTTP con administrador de la copia restaurada. La conexión estaba en solo lectura y los servicios externos aislados. Vistas compiladas; todas las migraciones figuraban aplicadas. Se conservan resultados previos de 2605 pruebas SQLite, 64 focalizadas PostgreSQL, PHPStan y Pint; no se ejecutó otra suite completa dentro de la imagen de producción sin dependencias dev.

Tras activar: `/up` y `/login` **200 HTTPS**, pantallas protegidas **302** para visitante anónimo; navegador mostró el login. No hubo sesión administrativa disponible para repetir navegación autenticada en producción. App/worker/scheduler activos y nginx saludable; app en production, debug OFF, America/Costa_Rica, PHP 8.4.26 y Laravel 12.64.0. Las huellas del menú y DatabaseDate coinciden con el código local; el manifiesto Vite coincide entre app y nginx (`6441ee3f808e93cee2a7db7f4503ce3750d62979b07f29c88e3f5d2c309a7ac4`). Durante la recreación hubo respuestas 404 transitorias del proxy; los reintentos concluyeron correctamente, sin reversión.

Se retiró únicamente el contenedor temporal `edudrive-release-restore-20260929` y su copia tmpfs. El respaldo recuperable sigue guardado en la entrega.

## Reversión

El override activo es `/opt/edudrive/compose.descubro-review.yaml`; su estado anterior está en `compose.before.yaml` dentro de esta entrega. Para revertir código, restaurar ese archivo y ejecutar desde `/opt/edudrive`:

```sh
docker compose -f compose.prod.yaml -f compose.bootstrap.yaml -f compose.descubro-review.yaml up -d --no-deps --no-build app queue-worker scheduler nginx
docker compose -f compose.prod.yaml -f compose.bootstrap.yaml -f compose.descubro-review.yaml exec -T app php artisan optimize
```

Conservadas imágenes anteriores: app `descubro-review-20260928r1`, nginx `descubro-review-20260927r1`. Revertir código no restaura datos; no restaurar el dump encima de producción sin evaluar escrituras posteriores. La publicación no valida el currículo ni abre el piloto a participantes.
