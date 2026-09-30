# Consolidación del trabajo acumulado — 28 de septiembre de 2026

> Estado reconciliado al 30 de septiembre: ver [CONTEXTO.md](../../CONTEXTO.md). Las notas cronológicas inferiores conservan estados históricos. La rama y el PR #1 ya existen; las publicaciones del 29 y las pruebas aprobadas posteriores sustituyen los bloqueos anteriores. El código del 29 sigue pendiente de commit/subida; esta actualización registra documentación.


## Alcance

Se conserva el trabajo de aplicación pendiente desde la base remota `a6803b7`: campus, cursos y herramientas de revisión, administración de usuarios y organizaciones, Pasaporte, simulaciones, narración y experiencia DESCUBRO. El primer commit local es `f40041d585b611b5f447afa2fd0948df7fdd1020`, con 544 archivos registrados mediante una lista explícita y verificación de huellas.

La rama de trabajo es `codex/consolidacion-20260928`. Esta consolidación es una entrega para revisión; no ejecuta migraciones, seeders ni despliegue en producción. La versión publicada sigue siendo `descubro-review-20260928r1`.

El segundo bloque incorpora las fuentes de contexto en `docs/contexto`, enlaces portables desde `CONTEXTO.md` y README, exclusión de `.claude/`, formato estándar y ajustes de contratos de tipos, fechas localizadas, lectura de contenido y comprobación del usuario autenticado. No cambia los criterios curriculares ni abre DESCUBRO a estudiantes.

## Protección del contenido

- Inventario inicial: 545 archivos, aproximadamente 5 MB antes de añadir fuentes de contexto.
- Excluida `.claude/settings.local.json`, configuración privada del equipo.
- No se incluyen `.env` reales, claves privadas, datos de usuarios, dumps de bases ni dependencias instaladas.
- La revisión de patrones de secretos no encontró claves privadas ni tokens GitHub/AWS en los archivos candidatos. Es una comprobación acotada, no una auditoría integral de seguridad.
- Las plantillas de entorno tienen credenciales vacías o valores nulos de ejemplo; las contraseñas de fixtures locales no se usan como credenciales de producción.

## Validación

La compilación Vite pasó (122 módulos); señaló el tamaño de un bloque de Three.js mayor de 500 kB. Pasaron seis pruebas del lector de voz y las 14 comprobaciones JavaScript existentes de escenas e instrumentos. Composer validó el archivo de dependencias.

La primera revisión estática encontró 47 observaciones y el formateador señaló archivos pendientes. Se prepararon correcciones de tipos y formato, sin desactivar reglas ni introducir un baseline para ocultarlas.

La primera ejecución general con memoria ampliada terminó con **57 fallos, 2518 pruebas con advertencias y 11 aprobadas; 8391 aserciones**. Esa ejecución no es una suite aprobada. Se identificaron fallos de expectativas de permisos, puerta de publicación pedagógica, fechas y configuración de almacenamiento de prueba, entre otros. El entorno inicial de la copia aislada carecía de `.env`; posteriormente se preparó una plantilla de entorno de prueba para las comprobaciones focalizadas. No se debe atribuir cada fallo a una sola causa sin reproducirlo.

Comprobación posterior: 60 pruebas focalizadas aprobadas (903 aserciones) para DESCUBRO, Pasaporte, reinicio, revisión editorial y gamificación. El análisis completo posterior redujo las 47 observaciones a dos; se corrigieron ambas y el análisis dirigido final informó cero errores. El formateador confirmó sin diferencias los últimos archivos señalados. Otras 11 pruebas de perfil y organizaciones pasaron (32 aserciones). No se repitió la suite general completa tras estos ajustes; sus fallos siguen pendientes de reconciliación y validación antes de integrar en main.

## Ensayo aislado posterior (28 de septiembre)

Se ejecutó HEAD `3d69d2a` completo (`vendor/bin/pest tests modules --compact`) en `/tmp/edudrive-validation-3d69d2a`, dentro de un contenedor sin red, SQLite en memoria y clave exclusiva de prueba. Dependencias y compilación se montaron en solo lectura; no se usó la base de producción. Resultado: **2533 aprobadas, 53 fallidas, 8400 aserciones, 218,11 segundos**. Registro del VPS: `/tmp/edudrive-validation-keyed.log`. Un ensayo anterior sin APP_KEY no sirve como resultado definitivo.

Los fallos incluyen permisos institucionales, publicación de fixtures pendientes de revisión pedagógica, diferencias de zona horaria, ventanas temporales de simulación y almacenamiento S3 sin credenciales de ensayo. Requieren análisis por causa; no son 53 defectos independientes confirmados. La zona horaria predeterminada es America/Costa_Rica: no cambiarla a UTC únicamente para ocultar diferencias de persistencia.

Se corrigió la expectativa obsoleta de AcademicReportTest: un administrador institucional sin reports.view no debe acceder a los cinco reportes globales. Reejecución focalizada: **5 pruebas aprobadas, 28 aserciones**. No se modificaron permisos de producción. Quedan los otros **52 fallos del ensayo general por resolver**; todavía no hay una nueva ejecución general aprobada. No integrar ni desplegar sobre la base de esta validación parcial.

### Corrección de sesiones y reportes de simulación

Se confirmó pérdida del offset al persistir fechas de sesiones: Eloquent escribía la hora recibida y la leía en America/Costa_Rica, desplazando el instante y rechazando telemetría/decisiones válidas. El repositorio ahora normaliza las fechas a la zona de lectura antes de guardarlas; no cambia la zona global ni desactiva la validación temporal. Se agregaron regresiones con offsets UTC, -06:00 y +02:00, incluyendo historial y límites de la ventana. No se modifican registros existentes: antes de desplegar, evaluar si hay sesiones históricas afectadas; no aplicar desplazamientos masivos sin evidencia.

También se actualizó la expectativa de acceso a los cuatro reportes globales de simulación para InstitutionalAdmin sin reports.view, manteniendo el rechazo. Todo el módulo de simulación pasó: **199 pruebas, 520 aserciones**. Esto resuelve otros nueve fallos del ensayo inicial; quedan **43 por reconciliar**, sin nueva suite general completa ni despliegue.

### Reconciliación de pruebas de matrícula

Las doce pruebas funcionales de gestión de inscripciones usaban InstitutionalAdmin, que actualmente no tiene enrollments.manage. Se cambió el actor de esos casos a SuperAdmin para ejercitar creación, validación, idempotencia y transiciones con autorización real, sin cambiar middleware ni permisos. Se añadieron seis casos independientes que verifican 403 para InstitutionalAdmin en creación individual/masiva/institucional y activar/completar/cancelar, comprobando además que la inscripción conserva su estado y no aparecen filas nuevas.

Resultado aislado: **25 pruebas de matrícula aprobadas, 65 aserciones**. Resueltos otros doce fallos originales; quedan **31 por reconciliar**, sin reejecución general completa. Esto documenta el contrato técnico actual, no decide ampliar las facultades institucionales del producto. Producción permanece sin cambios.

### Reportes institucionales y resolución de alcance

Se reconciliaron siete fallos de expectativas de permisos: InstitutionalAdmin no tiene reports.view. Las pruebas unitarias del resolver ejercitan alcance, exclusión y deduplicación con ManageUsers (permiso vigente del rol), y un caso nuevo verifica que ViewReports devuelve alcance vacío. La prueba HTTP verifica 403 en los cuatro reportes institucionales. Las tres pruebas del contrato de filtrado del controlador usan un resolver sustituido explícitamente con alcance limitado y un actor autorizado: conservan cobertura de filtro implícito, rechazo de organización ajena y filtro propio, pero no representan un permiso institucional real ni una integración de ese rol habilitada.

Validación aislada: **12 pruebas aprobadas, 34 aserciones**. Quedan **24 fallos originales por reconciliar**, sin nueva ejecución general completa. No se cambiaron permisos, controladores ni producción. Se inspeccionó el bloqueo de seeders: SafeCrossingPilotSeeder intenta aprobar/publicar automáticamente contenido con público pendiente de revisión; queda pendiente corregir su flujo de demostración sin falsear validación pedagógica.

### Carga de demostraciones sin publicación indebida

Los diez seeders que publicaban automáticamente consultan ahora CoursePublicationQualityGate antes de enviar/aprobar el curso. Solo CoursePedagogicalQualityRequired se captura: se registra el motivo y se conserva el borrador; otros errores siguen propagándose. No se alteraron criterios pedagógicos ni se marcaron lecciones como revisadas. El soporte está limitado a local/testing. La actualización de ciclismo admite borradores sin intentar reabrirlos y no modifica cursos en otros estados de revisión.

DatabaseSeederTest verifica creación local/testing, ausencia de cuenta de prueba en producción/staging, carga posterior al curso bloqueado e idempotencia; comprueba además que EDU-EXP-001 sigue en borrador, sin fecha de publicación y rechazado por la misma puerta pedagógica. **5 pruebas aprobadas, 21 aserciones** en copia aislada. Se resuelven tres fallos originales: quedan **21 pendientes**, sin suite general completa posterior ni despliegue. Estos cambios no retiran ni alteran cursos existentes en producción.

### Exportaciones, archivos y permisos administrativos

Las pruebas HTTP de exportaciones académicas, auditoría y archivos ahora usan Storage::fake('s3') y un generador de URL temporal de prueba con dominio .invalid. Siguen ejecutando los trabajos síncronos y comprobando su finalización, pero no requieren credenciales ni intentan acceder a S3 real. Esto no valida conectividad ni firma de URLs de producción.

Se corrigieron expectativas obsoletas de permisos en reportes globales, exportación de auditoría y acceso a archivos ajenos. Se mantiene el caso autorizado con SuperAdmin y se agregó rechazo de consulta/descarga de archivos ajenos para InstitutionalAdmin. No se modificaron permisos ni implementaciones de almacenamiento de producción.

Validación aislada: **31 pruebas aprobadas, 72 aserciones**. Resueltos siete fallos originales adicionales; quedan **14 por reconciliar**, principalmente fechas, contrato de contenido y validación de roles. No se ha repetido todavía toda la suite ni desplegado estos cambios.

### Contratos de contenido, roles y fecha explícita

Se actualizó la respuesta esperada de contenido para incluir learning_design: null cuando la lección no tiene diseño asociado, sin debilitar la comparación completa. La prueba de asignación a usuario inexistente ahora comprueba 422 y el error del campo user_id, coherente con la regla exists del FormRequest. El fixture de archivo de programas declara UTC explícitamente, como ya exigía su expectativa, en vez de depender de la zona local del proceso.

Validación aislada: **31 pruebas aprobadas, 108 aserciones** en esos tres archivos. Resueltos cuatro fallos originales más; quedan **10 fallos de fechas en persistencia por resolver** antes de repetir la suite general. No se cambiaron los contratos de producción ni la zona horaria de la aplicación.

### Fechas de versiones, matrículas y programas

Se normalizan las fechas antes de escribirlas en SQL a la zona que Eloquent emplea al leerlas, mediante DatabaseDate. Se aplica a publicación/archivo de versiones y programas, e inicio/fin/inscripción de matrículas. No se cambia APP_TIMEZONE ni se reinterpretan filas históricas. Las pruebas comparan instantes (timestamps), no una representación textual con offset arbitrario: el defecto previo de seis horas seguiría fallando.

Se añadieron seis combinaciones de desfases y zonas, comprobando que no se muta el objeto recibido y que la normalización es idempotente, más un caso null. **22 pruebas aprobadas, 84 aserciones** en los tres repositorios y el soporte de fechas. Resueltos tres fallos originales; quedan **7 pendientes de fechas**. Antes de desplegar sigue siendo necesario evaluar datos históricos afectados; no se autoriza una corrección masiva automática.

### Cierre de la suite funcional completa

Se aplicó la normalización de instantes a evaluación de proveedores IA, trabajos asíncronos, certificados, usuarios, consumidores API, eventos de aprendizaje y Pasaporte. También se normalizan los umbrales de búsqueda de trabajos antiguos y usuarios inactivos. Se conserva date_of_birth sin conversión: es una fecha civil, no un instante. Los siete repositorios pasaron **40 pruebas y 105 aserciones** antes del ensayo global.

La reejecución completa de `vendor/bin/pest tests modules --compact` en la copia aislada corregida terminó con **2604 pruebas aprobadas, 8582 aserciones, cero fallos, 176,26 segundos**. Registro: `/tmp/edudrive-validation-corrected.log`. Quedan resueltos los 53 fallos originales en esta suite. Existe un aviso de caché del complemento Pest porque vendor está montado en solo lectura; no es un fallo de prueba.

Este resultado cubre cambios locales aún sin commit ni subida. No se desplegó ni se modificó producción; no se migraron fechas históricas. Faltan formato/análisis estático de esta tanda, revisión de impacto en datos existentes y comprobación de despliegue antes de integrar/publicar. La aprobación funcional no acredita la revisión curricular ni un piloto educativo.

### Formato, análisis estático y precauciones de publicación

PHPStan completó el análisis configurado de app/modules (nivel 8, sin tests) con **cero errores**. La revisión general de Pint encontró 25 detalles de estilo en archivos de esta tanda; se corrigieron con el formateador y se recuperaron los resultados al repositorio local. La comprobación posterior de los **45 PHP modificados pasó**, y git diff --check no reportó errores. La suite completa de 2604 pruebas corresponde al código anterior a esos cambios exclusivamente de formato; no se repitió después del formateo.

Se documentó [el plan de revisión de fechas y despliegue](FECHAS-DESPLIEGUE-2026-09-28.md). No es una auditoría ejecutada sobre datos reales: quedan pendientes zona/tipos efectivos de producción, respaldo/restauración y contraste de muestras históricas. No se aplicaron correcciones de filas, commits, subida ni despliegue en esta tanda.

### Compatibilidad PostgreSQL posterior

Se creó PostgreSQL 17 aislado, sin puertos expuestos ni red externa, con credenciales desechables y datos sintéticos en tmpfs. El primer ensayo reveló seis fallos: fechas, comparación del orden de claves JSONB y continuación de una transacción abortada por unicidad. Al corregir el orden de claves se hizo visible otro desfase en aprendizaje.

Los modelos afectados preservan explícitamente el offset al serializar fechas para PostgreSQL; los repositorios conservan el offset de DateTimeInterface al reconstruir el dominio. Los umbrales de consultas también incluyen offset en PostgreSQL. No se cambió la zona global. Las pruebas JSON ordenan las claves antes de comparación estricta; la prueba de unicidad usa transacción/savepoint para poder comprobar que sigue existiendo una sola fila. Se agregó comprobación directa de epoch almacenado en users y límites de un segundo en trabajos asíncronos.

Resultado final focalizado: **64 pruebas PostgreSQL aprobadas, 206 aserciones; 81 pruebas SQLite aprobadas, 246 aserciones**. La suite general de 2604 aprobadas y el PHPStan anterior preceden esta tanda: deben repetirse. Producción sin cambios, sin commit/subida/despliegue ni actualización de datos históricos.

### Cierre final registrado el 29 de septiembre

Tras los ajustes PostgreSQL y el formateo, la suite completa SQLite terminó con **2605 pruebas aprobadas, 8585 aserciones, cero fallos, 175,59 segundos**. Registro aislado: `/tmp/edudrive-final-suite.log`. PHPStan completó app/modules al nivel 8 con **cero errores**; Pint `--test` aprobó los **63 PHP modificados**. El último ajuste fue una anotación PHPDoc condicional del retorno de DatabaseDate::normalize, sin cambio ejecutable.

Se mantiene la evidencia focalizada PostgreSQL de **64 pruebas y 206 aserciones**; no se presenta como suite general PostgreSQL. El contenedor PostgreSQL temporal fue eliminado junto con sus datos sintéticos. Producción no cambió. Antes de publicar faltan respaldo actualizado con restauración comprobada y revisión de fechas históricas; no se autoriza ajuste masivo de filas. Cambios locales aún sin commit ni subida.

## Subida y siguiente paso

**Actualización operativa del 29 de septiembre:** el usuario autorizó y se completó la publicación directa de `consolidacion-20260929r1`, con respaldo restaurado y verificaciones registradas en [PUBLICACION-2026-09-29.md](PUBLICACION-2026-09-29.md). Esto no implica commit, push ni integración de estas correcciones: siguen pendientes en Git.

**Actualización verificada el 28 de septiembre:** `git ls-remote` confirma la rama remota `codex/consolidacion-20260928` en `3d69d2ac7891d38499d039b828d1763046408bc5`, igual al HEAD local. `main` permanece en `a6803b7ffe80d0f975f232bba364d2c5a3dcf88f`. El bloqueo de subida descrito abajo es histórico y ya no representa el estado de esa rama. No se verificó aquí la existencia de PR ni la ejecución completa de CI.

La revisión posterior confirmó compilación Vite, seis pruebas de narración y catorce comprobaciones JavaScript. Cinco archivos centrales coinciden por SHA-256 con la imagen activa (CSS, aula, perfil, pasaporte y recorrido DESCUBRO). La plantilla del menú difiere: la corrección de contraste está versionada pero no publicada. Estos resultados no sustituyen la suite PHP completa.

La cuenta Git configurada, `AbelCampos2025`, puede leer el repositorio pero no escribir. La consulta de permisos devolvió `push: false`; una simulación de subida recibió HTTP 403. No se publicó la rama ni se creó una solicitud de revisión al documentar este bloqueo.

Para completar la subida hace falta permiso de escritura de esa cuenta en `EDUDRIVE-Official/edudrive-api`, o autenticar Git con una cuenta que ya lo tenga. No guardar tokens en este archivo ni en comandos versionados.

Una vez disponible el acceso, volver a consultar `origin/main`, publicar esta rama y abrir una solicitud de revisión con los resultados de validación. Resolver y volver a comprobar los fallos de la suite general antes de integrar en `main`. Una subida a `main` activa la construcción/publicación de imagen definida por CI; no se ha identificado un paso de despliegue del servidor dentro de ese workflow.

El avance curricular sigue en borrador. Publicar el código no acredita competencias, no valida el piloto ni autoriza una ampliación de público.
