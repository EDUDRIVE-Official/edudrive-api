# Integración editorial preparada y ensayada

Estado: revisión técnica preparada, no aplicada ni publicada. Fecha: 29 de septiembre de 2026.

## Implementación

- Los bloques de texto y escenario admiten `supplement` opcional en Markdown. Se conserva al convertir entre dominio y persistencia y usa las mismas restricciones de seguridad del texto: sin HTML crudo, imágenes ni enlaces inseguros. Límite de 20.000 caracteres; no afecta a bloques anteriores sin ese campo.
- El aula presenta los apoyos en un desplegable «Pistas, explicación y conversación» dentro de la página de cada bloque. Se preservan el formulario de decisiones, los IDs, la paginación y la finalización existentes. No añade otra fuente editorial paralela al curso: el apoyo forma parte del payload propuesto.
- La preparación distribuye misión/pistas/ampliación en la introducción, reflexión tras cada escenario y actividad/recapitulación/transferencia/guía adulta en el último bloque. No cambia los indicadores ni convierte `pending_review` en aprobación. La recapitulación está en el apoyo de la actividad, no duplica la página de cierre del aula.

## Plan y salvaguardas

`scripts/prepare-editorial-revision.php` genera por defecto un plan de solo lectura. Compara las cinco lecciones con la fuente del 20/09 y se detiene si cambiaron títulos, diseños, tipos o contenido. Verifica títulos de escenarios, IDs de opciones y respuesta correcta; valida cada payload con ContentBlockFactory.

El plan contiene IDs persistentes, posición, valores anteriores y propuestos de los 20 bloques. Archivo: [REVISION-EDITORIAL-PREPARADA-2026-09-29.json](REVISION-EDITORIAL-PREPARADA-2026-09-29.json). No contiene datos de estudiantes. Es un artefacto para revisión, no un comando de publicación ni una aprobación.

La opción `--rehearse` requiere APP_ENV=testing y una base llamada exactamente `edudrive_editorial_rehearsal`. Aplica el plan dentro de una transacción, renderiza ambos cursos con la cuenta administrativa de la copia y siempre ejecuta rollback. No existe opción para aplicar a producción.

## Verificación realizada

- 192 pruebas aprobadas, 825 aserciones: bloques, repositorio de contenido, gestión de contenido, matrícula web, vista editorial, prácticas por páginas y suplementos.
- PHPStan sin errores en las dos clases de dominio modificadas. Pint aprobó esos archivos y la prueba; corrigió el formato del script de preparación.
- PostgreSQL 17 aislado, sin red externa ni puertos expuestos, restaurado desde el respaldo de la publicación del 29/09. Ambos cursos con la propuesta aplicada respondieron HTTP 200 y mostraron los apoyos en la vista previa real del aula.
- Ensayo revertido y contenedor temporal eliminado. El respaldo original se conserva. No se enviaron correos ni se modificaron datos o código en producción.

No se repitió la suite general de 2605 pruebas. La prueba de renderizado no equivale a revisión visual de las diez animaciones ni a revisión docente, vial o de accesibilidad.

## Antes de publicar el contenido

Revisar la propuesta integrada y completar las revisiones humanas documentadas. Después preparar un aplicador transaccional con precondiciones de contenido y versión, auditoría y reversión; conservar historial, matrículas y avances. No usar seeders ni actualizar directamente los cursos sin esas comprobaciones. Al publicar, retirar o reubicar el acceso administrativo al borrador según corresponda.
