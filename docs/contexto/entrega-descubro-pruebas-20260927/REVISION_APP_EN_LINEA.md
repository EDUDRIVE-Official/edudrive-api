# Revisión de la app existente antes de actualizar DESCUBRO

Destino indicado por el usuario: https://app.edudrive.vr506.com.
Fecha de comprobación: 27 de septiembre de 2026.

La dirección existente es el destino de referencia para continuar la actualización. No se necesita definir otra dirección para esta comparación. La sesión del navegador está activa; la página de organizaciones responde. El proceso de aplicación en el servidor declara `APP_ENV=production` y `APP_DEBUG=false`.

## Compatibilidad comprobada

Se descargaron únicamente cinco archivos de código desde el contenedor activo. Las huellas SHA-256 de los cinco coinciden exactamente con los respaldos anteriores al desarrollo de DESCUBRO. El parche del paquete pasó `git apply --check` contra esa copia del código publicado:

- Rutas web de Academic.
- Servicio de reinicio del historial de aprendizaje.
- Vista de actividad de aprendizaje.
- Vista de Mi perfil.
- Vista del Pasaporte Vial.

Por tanto, no se encontraron conflictos en esos puntos de integración. Esto verifica compatibilidad de los archivos examinados, no equivale a ejecutar la nueva versión completa en el servidor.

Las tablas `users` e `identity_student_learning_resets` existen. La tabla `academic_descubro_progress` aún no existe; la actualización requiere revisar y aplicar su migración específica, después de respaldar la base. No se consultaron filas de usuarios ni se migraron datos. Se confirmó que `backup:database` está disponible, pero no se ejecutó ni se comprobó todavía un respaldo nuevo.

## Versión que se debe conservar para volver atrás

La aplicación funciona con la imagen `edudrive-api:bootstrap`, identificador:

`sha256:0d0c210bff08922c969fabab0c0e9498355e65a0f517e99d21d312c2e5a8d8f3`

Nginx utiliza la imagen identificada como:

`sha256:baa0745c789ad4e2a00b86a1c48acadaa3c98ab1933555ca0125011c719999a7`

La instalación usa `/opt/edudrive/compose.prod.yaml` junto a `/opt/edudrive/compose.bootstrap.yaml`. El código de aplicación está dentro de la imagen; copiar archivos al checkout del servidor no actualiza por sí solo el contenedor activo. Antes del despliegue se deben volver a verificar estas referencias y conservar ambas imágenes.

## Decisión pendiente y siguiente implementación

El paquete actual mantiene DESCUBRO bloqueado en producción. Su opción de staging no lo hará visible en este destino; no debe cambiarse el ambiente del servidor para sortear ese control.

Se preguntó al usuario si la primera actualización debe quedar disponible únicamente para revisión administrativa o también para estudiantes. Después de esa respuesta se debe implementar y probar el control de acceso correspondiente, preparar la imagen candidata desde la base publicada con los cambios específicos, generar un respaldo verificable, revisar la migración y desplegar/verificar en la misma app.

No se han cambiado permisos, publicado imágenes, copiado código al servidor, aplicado migraciones ni reiniciado servicios durante esta revisión.
