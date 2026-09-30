# Precauciones de despliegue: fechas

**Cierre operativo del 29 de septiembre:** publicada `consolidacion-20260929r1` tras respaldo/restauración y comprobación de 86 fechas existentes sin desplazamientos en copia aislada. Las prohibiciones y pendientes de despliegue siguientes describen fases previas. No se demostró exactitud histórica de origen ni se corrigieron filas. [Evidencia y reversión](PUBLICACION-2026-09-29.md).

## Bloqueo confirmado en producción (consulta de solo lectura)

Actualización registrada el 29 de septiembre: la reproducción aislada permitió corregir la pérdida de offsets en escritura y lectura. **64 pruebas PostgreSQL (206 aserciones)** pasaron. El cierre general posterior pasó **2605 pruebas SQLite (8585 aserciones)**, PHPStan nivel 8 sin errores y Pint en los 63 PHP modificados. Se conserva la zona global original. El despliegue continúa pendiente de respaldo actualizado/restauración y auditoría histórica. El diagnóstico siguiente explica el fallo anterior; no se ejecutó toda la suite en PostgreSQL.

La aplicación efectiva usa America/Costa_Rica, environment=production y debug=false. La sesión PostgreSQL de Laravel devuelve TimeZone=UTC y no tiene timezone configurado en la conexión. El esquema combina timestamps sin zona (academic_enrollments) con timestamps con zona (simulation_sessions, certificates, road_passports, users). Incluso date_of_birth aparece como timestamp con zona: su migración a fecha civil requeriría una revisión aparte.

**No desplegar todavía la normalización local de fechas.** Eloquent serializa sin offset: un valor convertido a hora local puede ser interpretado como UTC por una columna timestamptz. Un SELECT con fechas ficticias confirmó un desfase de -21600 segundos. Las 2604 pruebas aprobadas usan SQLite y no demuestran compatibilidad de este comportamiento con PostgreSQL real.

La reproducción y corrección con datos sintéticos ya se completaron. El código preserva offsets en PostgreSQL al serializar, reconstruir fechas y consultar umbrales. No se modificaron conexión, tipos ni datos reales. Cambiar globalmente la zona del servidor podría modificar la interpretación de datos históricos.

Respaldo localizado: /opt/edudrive/releases/descubro-review-20260927r1/before-descubro.dump (465079 bytes). Cabecera/listado legibles con pg_restore, creado 2026-09-28 00:46:28 UTC, PostgreSQL 17.11, 483 entradas. Esto NO equivale a restauración validada ni a respaldo actualizado para un próximo despliegue. No se creó ni restauró respaldo en esta comprobación.

Composición existente: compose.prod.yaml, compose.bootstrap.yaml y compose.descubro-review.yaml. No se modificó ninguno ni se inspeccionaron sus secretos.

## Alcance comprobado

El código nuevo conserva el instante de las fechas recibidas con offset al escribirlas en columnas SQL sin offset. Normaliza a `config('app.timezone')`, la misma zona utilizada al recuperar las fechas. No cambia la configuración global ni ejecuta migraciones de datos. La fecha de nacimiento no se convierte: es una fecha civil.

Se cubren sesiones de simulación, versiones de cursos, matrículas, programas, certificados, usuarios, trabajos asíncronos, consumidores API, eventos de aprendizaje, Pasaporte y evaluaciones de proveedores IA. No se afirma que todas las rutas temporales del sistema hayan sido auditadas.

## Datos históricos: no aplicar ajustes masivos

Una columna sin offset no permite saber si el valor original llegó en UTC, hora local u otra zona. Restar seis horas a toda una tabla puede dañar registros correctos. La nueva normalización tampoco recupera información perdida en filas anteriores.

Antes de desplegar:

1. Conservar como referencia la zona efectiva y los tipos de columnas ya comprobados en producción; volver a comprobarlos si cambia la configuración antes del despliegue.
2. Obtener respaldo recuperable y comprobar su restauración en un entorno separado.
3. Identificar por módulo los orígenes de fechas: formularios locales, API con offset y datos de demostración. Comparar una muestra con evidencia externa de hora, sin exportar datos personales al informe.
4. Prestar especial atención a caducidad de certificados y credenciales, ventanas de simulación y selección de usuarios inactivos. Mientras no se verifique su histórico, no ejecutar limpiezas o caducidades masivas basadas en esas fechas.
5. Si hay filas demostrablemente incorrectas, preparar una corrección separada, con identificadores exactos, valores anteriores/nuevos y aprobación. No inferir el offset solamente del valor almacenado.

## Publicación y verificación

Mantener la zona efectiva actual. Usar la composición real del servidor, incluidos sus overrides; no reemplazarla por una plantilla genérica. No ejecutar seeders de demostración ni migraciones de fechas durante este despliegue.

Comprobar en staging la ida y vuelta de un instante UTC y otro local, sus límites de caducidad y filtros temporales. Después verificar login, cursos, perfil y Pasaporte con cuentas autorizadas, sin generar acreditaciones ficticias.

Registrar versión previa/nueva y hora de activación. Si se necesita revertir código, conservar la trazabilidad de las escrituras realizadas durante la nueva versión: volver a una imagen anterior no restaura los datos. Cualquier restauración debe considerar las escrituras posteriores al respaldo.

Estado: configuración y tipos comprobados; corrección validada en PostgreSQL focalizado y suite completa SQLite. Histórico sin auditar/corregir, restauración pendiente y publicación aún no realizada. No hubo commit ni subida de esta tanda.
