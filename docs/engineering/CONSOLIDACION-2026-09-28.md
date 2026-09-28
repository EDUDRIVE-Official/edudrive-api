# Consolidación del trabajo acumulado — 28 de septiembre de 2026

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

## Subida y siguiente paso

La cuenta Git configurada, `AbelCampos2025`, puede leer el repositorio pero no escribir. La consulta de permisos devolvió `push: false`; una simulación de subida recibió HTTP 403. No se publicó la rama ni se creó una solicitud de revisión al documentar este bloqueo.

Para completar la subida hace falta permiso de escritura de esa cuenta en `EDUDRIVE-Official/edudrive-api`, o autenticar Git con una cuenta que ya lo tenga. No guardar tokens en este archivo ni en comandos versionados.

Una vez disponible el acceso, volver a consultar `origin/main`, publicar esta rama y abrir una solicitud de revisión con los resultados de validación. Resolver y volver a comprobar los fallos de la suite general antes de integrar en `main`. Una subida a `main` activa la construcción/publicación de imagen definida por CI; no se ha identificado un paso de despliegue del servidor dentro de ese workflow.

El avance curricular sigue en borrador. Publicar el código no acredita competencias, no valida el piloto ni autoriza una ampliación de público.
