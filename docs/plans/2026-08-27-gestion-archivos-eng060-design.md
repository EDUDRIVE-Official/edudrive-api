# ENG-060 — Gestión de archivos: alcance acordado

Segunda historia de la Fase 12 — Administración y operación. A diferencia de ENG-059, esta historia introduce un concepto de dominio propio (archivos almacenados) que no es una preocupación administrativa en sí — se le da su propio módulo nuevo, `Modules\FileStorage`, en vez de extender `Modules\Admin`: las fases del roadmap son agrupaciones de planificación, no límites de contexto acotado de DDD.

## Estado previo encontrado (investigación, no una decisión del usuario)

- El contenedor **MinIO** ya existe en `compose.yaml` (servicio `minio`, puertos 9100/9101, credenciales `edudrive`/`edudrive_local_password`), pero **no está conectado**: falta el paquete `league/flysystem-aws-s3-v3` y las variables `AWS_*`/`MINIO_*` en `.env`. El disco `s3` en `config/filesystems.php` ya existe con la forma correcta (lee `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT`, etc.) — solo falta cablearlo.
- No existe ningún servicio antivirus (ClamAV u otro) en la infraestructura.
- No existe ningún módulo, agregado o endpoint de archivos/carga/adjuntos en todo el backend — completamente *greenfield*.
- No existe ningún concepto de cuota de almacenamiento.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **MinIO**: se conecta de verdad. Se instala `league/flysystem-aws-s3-v3`, se agregan las variables `AWS_*` a `.env`/`.env.example` apuntando al contenedor `minio` ya corriente (`AWS_ENDPOINT=http://minio:9000`, `AWS_USE_PATH_STYLE_ENDPOINT=true`), y el disco `s3` ya configurado se usa tal cual para guardar los archivos reales.
2. **Antivirus**: solo un estado de escaneo (`pending`/`clean`/`infected`) que empieza en `pending` — sin integración real con ningún motor. Se expone un ajuste manual (`files.manage`) como punto de extensión para cuando exista un escáner real, pero nada lo cambia automáticamente todavía.
3. **Carga y descarga**: carga por el backend (multipart en el cuerpo de la petición); descarga vía URL temporal firmada (`Storage::disk('s3')->temporaryUrl()`), nunca reenviando los bytes a través de Laravel.
4. **Cuotas**: cuota simple por usuario, verificada sumando el tamaño de los archivos ya guardados por ese usuario antes de aceptar una carga nueva. El límite se lee de una clave de `SystemSetting` (`file_storage_quota_bytes`, ya construido en ENG-059) con un valor por defecto si no está configurada — sin cuotas por organización ni por rol.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Nuevo módulo `Modules\FileStorage`** en vez de extender `Modules\Admin`: almacenamiento de archivos es un concepto de dominio distinto (con sus propias reglas: cuota, escaneo, descarga autorizada), no una vista administrativa sobre otro módulo. Depende de `Modules\Admin\Domain\Repositories\SystemSettingRepository` para leer el límite de cuota — dependencia entre módulos documentada, mismo criterio que la dependencia de `Modules\Admin` sobre `Modules\Audit` en ENG-059.
- **Verificación de cuota antes de guardar bytes**: el handler de carga calcula el uso actual y rechaza (`FileQuotaExceeded`, 422) *antes* de escribir en MinIO — evita objetos huérfanos en el bucket si se rechaza la carga.
- **Bucket verificado por un comando de consola, no en cada arranque**: `php artisan files:ensure-bucket` (idempotente, usa el SDK de AWS directamente) — mismo espíritu que las migraciones: un paso de aprovisionamiento explícito, no un efecto secundario oculto en cada petición.
- **Eliminación real, no un estado "retirado"**: a diferencia de `Achievement`/`Badge`/`Challenge` (que nunca se borran, solo cambian de estado), un archivo eliminado se borra de verdad de la base de datos y de MinIO — mantener una fila "retirada" contaría espacio contra la cuota sin liberar los bytes reales.
- **El estado de escaneo no bloquea la descarga**: como ningún mecanismo real lo cambia automáticamente en esta historia, bloquear la descarga mientras esté `pending` haría que ningún archivo fuera descargable jamás. Se expone como informativo únicamente; la aplicación de una política real (bloquear `infected`, esperar a `clean`) queda diferida a cuando exista un escáner de verdad.
- **Patrón anti-fuga de pertenencia**: consultar/descargar/eliminar el archivo de otro usuario requiere `files.view`/`files.manage`; sin ese permiso, un archivo ajeno responde `FileNotFound` igual que uno inexistente — mismo criterio que `RoadPassport`/`SimulationSession`/`Notification`.
- **Límite por petición además de la cuota acumulada**: cada archivo individual no puede superar 20 MB (validación de `FormRequest`, independiente de la cuota total acumulada del usuario).
- **Autoservicio de carga, listado propio y eliminación propia**: cualquier usuario autenticado puede subir, listar y eliminar sus propios archivos sin permiso especial — mismo criterio que unirse no es autoservicio en `Challenge`, pero aquí sí, porque subir un archivo propio (ej. un adjunto, un avatar) es una acción básica de la plataforma, no una acción con efecto en otro usuario.

## Incluye (del roadmap)

- MinIO.
- Carga segura.
- Descarga autorizada.
- Metadatos.
- Antivirus.
- URLs temporales.
- Cuotas.

## Diferido explícitamente

- Integración real con un motor antivirus (requiere agregar un servicio nuevo a la infraestructura).
- Carga directa con URL prefirmada (el cliente sube directo a MinIO).
- Aplicación de una política de bloqueo de descarga según el estado de escaneo.
- Cuotas por organización o por rol; límite de cuota configurable por archivo individual.
- Consulta administrativa de "todos los archivos de todos los usuarios" (solo autoservicio + consulta puntual por id con permiso).
- Metadatos adicionales definidos por el usuario (categorías, descripciones libres).
