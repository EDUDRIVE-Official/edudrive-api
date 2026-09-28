# Entrega DESCUBRO para revisión en línea

Fecha: 27 de septiembre de 2026. Estado: preparada, sin desplegar.

Actualización posterior: el usuario indicó continuar con la app existente `app.edudrive.vr506.com`. La comparación de solo lectura contra esa instalación ya confirmó la compatibilidad del parche. Consultar `REVISION_APP_EN_LINEA.md`: la decisión pendiente es quién verá DESCUBRO allí. Las instrucciones de staging que siguen describen el paquete original; no habilitan la experiencia en la app existente, que usa producción.

Esta entrega reúne «Cruzar con acompañamiento»: tres explicaciones, cuatro decisiones con retroalimentación, ilustraciones, avance por cuenta, resumen de habilidades, lectura opcional en español y repaso sin reinicio. Incluye acceso desde Mi perfil y tarjeta interna en Pasaporte Vial. Mantiene la distinción entre práctica digital y dominio observado.

## Qué contiene

- `files/`: archivos nuevos propios de DESCUBRO, configuración y pruebas.
- `integration.patch`: cambios puntuales en cinco archivos compartidos: rutas, perfil, Pasaporte, actividad de aprendizaje y reinicio de historial.
- `manifest.json`: inventario de 28 archivos, huellas SHA-256 y referencia del árbol local.

Es un paquete de código para integrar y revisar, **no una imagen lista para desplegar**. El HEAD registrado no contiene todos los cambios del árbol local. No se debe construir una versión de publicación usando indiscriminadamente ese árbol: contiene otros desarrollos pendientes. El paquete excluye credenciales, archivos `.env`, datos de cuentas y dependencias instaladas.

## Activación prevista

En un sitio de pruebas separado, configurar:

```dotenv
APP_ENV=staging
APP_DEBUG=false
DESCUBRO_STAGING_ENABLED=true
```

El interruptor es falso por defecto. Solo permite habilitar la experiencia en `staging`; nunca la habilita en `production`, aunque se configure como verdadero. Local y testing conservan el comportamiento actual. Perfil y Pasaporte consultan el mismo control de disponibilidad. Las rutas siguen exigiendo inicio de sesión y los formularios conservan sus protecciones existentes.

Utilizar dominio HTTPS, base de datos, almacenamiento, sesiones y cuentas de revisión separados de producción. No cambiar el ambiente de la app pública a local o staging. Las cuentas autenticadas del sitio de staging podrán acceder; por ello el sitio debe contener únicamente las cuentas previstas para revisión. Esta entrega no modifica permisos ni crea cuentas.

## Trabajo previo al despliegue

1. Confirmar la dirección de pruebas, el servidor y la base de código que se utiliza actualmente allí. Aún no se ha confirmado un sitio de staging.
2. Integrar los archivos nuevos y revisar el parche sobre una rama compatible. Verificar `git apply --check integration.patch` antes de aplicarlo. No reemplazar archivos compartidos enteros ni resolver conflictos aceptando todo automáticamente.
3. Confirmar las dependencias existentes: autenticación web, usuarios, vistas de perfil/Pasaporte, resumen y servicio de reinicio de historial, su tabla `identity_student_learning_resets` y registro de migraciones del módulo Academic. El paquete no incluye todos esos desarrollos previos.
4. Ejecutar las pruebas en esa base integrada, construir los assets e imágenes de app y nginx desde la misma revisión y registrar sus referencias inmutables. Las pruebas locales no sustituyen la validación de la versión integrada.
5. Revisar las migraciones pendientes en el destino. Esta experiencia añade únicamente `modules/Academic/Infrastructure/Persistence/Migrations/2026_09_25_000001_create_academic_descubro_progress_table.php`. Respaldar la base antes de aplicarla. No ejecutar todos los seeders ni todas las migraciones pendientes por defecto.
6. Desplegar exclusivamente en el destino de pruebas confirmado y verificar salud, acceso autenticado y recorrido. El script existente `scripts/deploy.sh` está vinculado a `compose.prod.yaml` y aplica todas las migraciones pendientes: **no utilizarlo sin adaptación para esta entrega de pruebas**.

La infraestructura documentada del proyecto corresponde a `app.edudrive.vr506.com`. La revisión posterior se conectó al servidor únicamente para comparar código y configuración no secreta. No se modificó el servidor, no se publicaron imágenes, no se hicieron pushes ni se crearon dominios.

## Comprobación de la versión en línea

Comprobar acceso anónimo, sesión autenticada, guardado tras salir y volver a ingresar, conflictos entre pestañas, respuesta incorrecta y reintento, resultado, perfil/Pasaporte y repaso que conserva el avance. Confirmar que no se emitan certificados, puntos ni evidencias de dominio. La configuración desactivada debe devolver 404 para lectura y escritura de DESCUBRO.

Probar narración en los dispositivos de revisión. La instalación de la voz española en este Windows no instala voces en los equipos de quienes usen la web: la narración depende de las voces disponibles en cada navegador/equipo. Si falta voz española, la alternativa visible es lectura con acompañante.

Validación de esta preparación: siete pruebas, 115 aserciones en contenedor aislado con SQLite en memoria; incluye el control por ambiente, acceso autenticado, GET/POST de staging habilitado/deshabilitado, bloqueo de producción, recorrido y repaso. Formato PHP revisado. No es validación pedagógica ni prueba remota.

## Retirada de la versión de pruebas

Desactivar `DESCUBRO_STAGING_ENABLED`, regenerar la caché de configuración en staging y comprobar que las rutas quedan cerradas. Si fuera necesario, restaurar las imágenes previas compatibles. Conservar la tabla de avance; no ejecutar su migración inversa como parte de una retirada rutinaria porque elimina datos. Cualquier restauración de base requiere una decisión específica y un respaldo verificado.

## Pendiente para cerrar la publicación

Dirección y servidor de pruebas confirmados; base integrada y revisión identificada; imagen construida/verificada; migración revisada en ese destino; despliegue y comprobación remota. Hasta completar estos puntos, el cambio sigue disponible únicamente en la app local.
