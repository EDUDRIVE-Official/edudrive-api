# P912 · Ensayo interno del flujo

Estado al 2026-09-16: implementado y con pruebas PHP dirigidas aprobadas; pendiente de revisión en navegador y ensayo docente. No habilita aplicación con menores ni demuestra eficacia educativa.

## Alcance

Actualización de revisión local: creada únicamente la tabla `academic_pilot_runs` y probado en navegador el diagnóstico, la práctica con pista/reintento y el cierre de comprobación independiente con datos ficticios. Ver registro en `UNIDAD-MODELO-U01.md`. Esto actualiza la situación de migración descrita en la verificación técnica inicial más abajo; no habilita uso con menores.

La unidad modelo se consulta en `/pilot-instruments/unit`, enlazada desde el inicio del ensayo. Es una guía docente de solo lectura, no una nueva lección publicada. Su fuente, alcance y matriz están descritos en `UNIDAD-MODELO-U01.md`.

Ruta `/pilot-instruments`, solo en entorno `local` o `testing`, con sesión y permiso `courses.manage`. Cada persona autorizada accede únicamente a sus propios ensayos. No se añade al recorrido del estudiante ni sustituye lecciones publicadas.

Cuatro formas: diagnóstico, práctica, comprobación independiente y seguimiento. Se conserva una copia de las consignas y la versión del banco al iniciar. No se envían criterios del evaluador. Solo la práctica ofrece pistas registradas y devolución tras guardar una respuesta; en las otras formas la devolución sigue a cargo del facilitador, después del cierre.

Se admiten respuestas incorrectas, incompletas, vacías y cierre sin responder. Cada guardado agrega un evento, conserva la primera respuesta y distingue apoyo de acceso de ayuda de contenido declarada. Una ayuda registrada para el ítem mantiene las respuestas posteriores como formativas. No detectar ayuda no acredita independencia ni dominio. Los ítems sin registro no prueban que no fueron presentados.

Hay control de propietario, revisión concurrente y cierre definitivo. El límite de 200 eventos permite cerrar incluso al alcanzarlo. No escribe en progreso, matrículas, Pasaporte ni certificados. La tabla propia conserva únicamente ensayos ficticios; no introducir datos personales.

## Verificación antes de usar

1. En una base local de prueba, aplicar únicamente la migración `2026_09_16_000001_create_pilot_instrument_runs.php` con el procedimiento habitual del proyecto. No ejecutar seeders ni migrar datos publicados.
2. Ejecutar `php vendor/bin/pest modules/Academic/Tests/Feature/PilotInstrumentRunsTest.php`. Usar la ruta explícita: la suite predeterminada de `phpunit.xml` no incluye los módulos. Revisar además formato y análisis estático del proyecto.
3. Acceder con una cuenta autorizada. Probar una respuesta incorrecta, otra vacía, un reintento con ayuda y el cierre con pendientes. Revisar con teclado y lector de pantalla.
4. Verificar pistas agotadas, aislamiento entre usuarios, actualizaciones desde dos pestañas y rechazo en producción. Comprobar que no se crean progreso ni certificados.

La migración no ha sido aplicada a la base de desarrollo. Se localizó PHP 8.4 en el contenedor existente `edudrive-app`, fuera del PATH de Windows. Las pruebas se ejecutaron con SQLite en memoria y sin configuración almacenada en caché. Pasaron 34 pruebas (95 aserciones): 13 del ensayo, 9 de diseño pedagógico y 12 de finalización de lecciones. Incluyen formularios HTTP, presentación de la vista, confirmación de datos ficticios y escape de respuestas. Pint aplicado a los archivos nuevos. La compilación web y el comprobador editorial también pasaron. Esto no sustituye una revisión visual ni una validación pedagógica.

## Siguiente condición de salida

Verificación final del código del ensayo: PHPStan sin errores en servicio/controlador, Pint aprobado y repetición de las 13 pruebas del ensayo (39 aserciones) aprobada después de las últimas correcciones. La suite completa del repositorio no se ejecutó.

Ensayo docente con datos ficticios y revisión de instrumentos por especialistas. Antes de estudiantes: protocolo de datos y autorizaciones, modalidad de respuesta, registro de presentación, observador y contexto, mapeo por indicador y devolución revisada. No abrir este controlador en producción como atajo para esa integración.
