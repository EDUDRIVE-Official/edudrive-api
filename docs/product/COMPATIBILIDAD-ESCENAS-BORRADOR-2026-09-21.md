# Compatibilidad técnica del borrador y las escenas

21 de septiembre de 2026. Revisión del código local; no despliegue ni aprobación humana.

## Hallazgo y corrección preparada

Cuatro recorridos correctos existentes atraviesan la calle automáticamente: atajo entre automóviles, esquina con poca visibilidad, dos caminos y rampa bloqueada. En el borrador esas decisiones solo eligen un punto de espera o una alternativa; todavía no autorizan cruzar.

Se agregó el modo explícito `stop_at_decision_point: true` a esos cuatro escenarios del borrador. El dominio acepta únicamente un booleano; conserva intacto el formato de escenarios anteriores cuando falta la propiedad. Las vistas pasan el modo a sus controladores y motores. El recorrido positivo termina ahora en la misma acera, antes del tramo que cruzaba. La línea dibujada también termina allí.

El valor predeterminado conserva los recorridos anteriores. No se añadieron propiedades a contenidos publicados ni se ejecutaron seeders. Esta corrección está en el código local y el borrador, no en el sitio público.

## Cobertura de las diez decisiones

| Escenario | Resultado de inspección local | Trabajo pendiente |
|---|---|---|
| El atajo entre automóviles | Modo de espera preparado; IDs conservados | Revisar movimiento, posiciones y feedback en navegador |
| La esquina con poca visibilidad | Modo de espera preparado; IDs conservados | Revisar vista bloqueada, van y alternativa |
| El autobús en la parada | Tiene escena dedicada; ramas por acierto/error | Las dos opciones incorrectas comparten paso visual; diferenciar frente/detrás si se representan ambas |
| Movimientos que se cruzan | Tiene escena y ramas por ID | Comprobar ambas trayectorias y correspondencia de cada opción |
| Dos caminos a la escuela | Modo de espera preparado | Personaje existente en silla de ruedas frente a relato Luna/abuelo: armonizar sin perder inclusión |
| La rampa está bloqueada | Modo de espera preparado | Comprobar que la ayuda no parezca empuje sin consentimiento; revisar acompañante |
| Vehículo aún en movimiento | Render genérico en selector de escenarios | Preparar ascenso con detención y espera, no reutilizar descenso como si fueran equivalentes |
| Ascenso junto a la calzada | Render genérico | Mostrar lado de acceso y tarea adulta |
| Bajar entre vehículos | Render genérico | Evaluar adaptación de escena de descenso piloto; aún no está conectada por este título |
| Después del autobús | Render genérico | Mostrar dentro → descenso → acera → observación, sin cruce automático |

Que una escena tenga render genérico no impide leer y responder, pero no cumple por sí sola la experiencia visual buscada. La existencia de la escena piloto de descenso no demuestra integración con estas lecciones.

Otro comportamiento a revisar: las escenas de autobús/movimientos permiten seleccionar antes de abrir, pero al abrir inicializan paso cero. No se da por resuelta aquí la sincronización entre selección y reapertura. No se declara QA visual completa.

## Pruebas ejecutadas

- `node scripts/check-pedestrian-decision-paths.mjs`: cuatro recorridos, 4.004 muestras de curva. Ninguna muestra del modo de espera sale de la coordenada lateral de su acera. Coordenadas históricas preservadas. Prueba geométrica, no inspección visual.
- `node scripts/check-editorial-scene-bindings.mjs`: 72 selecciones (tres opciones × seis órdenes × cuatro escenas), reinicio, transmisión del modo y valores históricos. Ejecuta controladores reales con motores simulados; no prueba WebGL ni Blade renderizado.
- `scripts/check-editorial-draft.ps1`: cinco lecciones, diez escenarios, distribución A=4/B=3/C=3 y correspondencia de IDs.
- `php scripts/check-scenario-decision-mode.php`: 20 comprobaciones del dominio, conservación del formato anterior, booleanos válidos, rechazo de tipos inválidos y lectura de los diez payloads del borrador. Ejecutado en contenedor remoto sin red y de solo lectura; sin aplicación de cambios a servicios o datos. Primer intento de montaje rechazado por solo lectura, corregido usando archivos de prueba temporales.
- `npm run build`: compilación correcta. Permanece aviso de tamaño superior a 500 kB en un paquete de Three.js; no se presenta como fallo de compilación.

## Siguiente condición de salida

Probar en una vista previa de acceso restringido, con las cinco lecciones completas y sus nuevas opciones, antes de publicar. Resolver los desajustes de personajes y movimientos, las cuatro escenas genéricas y las alternativas de teclado/movimiento reducido. Luego obtener las revisiones humanas; ninguna prueba técnica sustituye sus dictámenes.
