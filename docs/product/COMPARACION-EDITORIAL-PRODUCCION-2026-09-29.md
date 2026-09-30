# Borrador editorial frente a cursos en producción

Actualización posterior: [integración preparada y ensayada](INTEGRACION-EDITORIAL-2026-09-29.md), todavía sin aplicar a producción.

Fecha: 29 de septiembre de 2026. Comparación de solo lectura con la app publicada `consolidacion-20260929r1`; no se actualizaron contenidos, estados, avances ni matrículas.

## Resultado

El formato y parte de los componentes del prototipo sí llegaron al aula. Los textos nuevos y apoyos editoriales no fueron trasladados. No es necesario reconstruir el sistema de cursos, matrícula o finalización.

| Lección del borrador | Curso publicado | Bloques diferentes frente al borrador | Coincide con la fuente del 20/09 |
| --- | --- | --- | --- |
| Reto 1: ¿Por dónde cruzamos? | EDU-EXP-001 | 4 de 4 | Sí: bloques y diseño |
| Reto 2: Todos compartimos la vía | EDU-EXP-001 | 4 de 4 | Sí: bloques y diseño |
| Reto 3: Una ruta segura para todas las personas | EDU-EXP-001 | 4 de 4 | Sí: bloques y diseño |
| Subir desde un lugar protegido | EDU-EXP-003 | 4 de 4 | Sí: bloques y diseño |
| Bajar y volver a observar | EDU-EXP-003 | 4 de 4 | Sí: bloques y diseño |

Son cinco lecciones repartidas en dos cursos, no cinco cursos independientes. Ambos cursos tienen estado `published`, mientras los diseños de estas cinco lecciones siguen en `pending_review`. Publicación del curso y revisión del diseño no son equivalentes.

Se comparó por ID de lección, tipo y contenido completo de cada bloque y diseño, ignorando el orden de claves JSON pero conservando el orden de bloques/opciones. Los 20 bloques distintos comprenden diez textos/actividades y diez escenarios. Se recuperaron sus identificadores persistentes; no se detectó divergencia de contenido frente a la fuente archivada del 20 de septiembre. Esto no certifica revisiones especializadas ni compatibilidad visual de cada rama.

## Qué ya está integrado

- `courses.learn`: preparación, una página por bloque y cierre; finalización y validaciones del servidor existentes.
- Retroalimentación común en los escenarios y conservación de decisiones al cambiar de página dentro de la visita.
- Cuatro variantes de pasajeros conectadas al componente 3D mediante `courses.blocks.scenario`, además de las escenas peatonales.
- El rediseño visual del campus. No confundirlo con actualización de contenidos persistidos.

## Qué falta trasladar

1. Los 20 bloques reescritos: introducciones, actividades, contexto/preguntas, opciones y explicaciones. Mantener los IDs persistentes de bloques y elecciones, y comprobar movimientos frente a la nueva redacción.
2. Los apoyos de `resources/curriculum/editorial/page-support.php`: pistas, misión, ampliación, reflexión, pasos de actividad y recapitulación. Actualmente los consume la vista editorial, no `courses.learn`.
3. Los campos editoriales complementarios: consignas, guía adulta y casos de transferencia. Definir su ubicación en los bloques/diseño existentes; no dejarlos como una segunda fuente de contenido paralela.
4. Resolver público, indicadores y revisión especializada, sin cambiar `pending_review` a aprobado por una aceptación visual o una prueba técnica.

La vista editorial tiene cinco páginas por lección. El aula usa preparación + bloques + cierre; compartir paginación no significa que el contenido o la cantidad de páginas sea idéntica. Se debe decidir una sola presentación final para evitar duplicación de introducciones o cierres.

## Siguiente implementación recomendada

Preparar una revisión de contenido de estas cinco lecciones, no nuevos cursos: mapa explícito origen/destino, precondiciones de versión/contenido, respaldo de valores anteriores y reversión. Reutilizar matrícula, registro de decisiones y finalización existentes. No ejecutar seeders sobre cursos publicados ni reiniciar avances. Probar la propuesta integrada con los IDs reales en una copia aislada y verificar las diez escenas antes de modificar producción.

Después de completar la revisión de contenido y autorizar su publicación, retirar o reubicar el acceso administrativo «Probar borrador». Cambiar solamente ese rótulo no integra el material.

## Evidencias

- Fuente: `docs/product/review-camino-pasajero-source-2026-09-20.json`.
- Borrador usado por la pantalla: `resources/curriculum/editorial/camino-pasajero-v1.json`.
- Controlador: `modules/Academic/Presentation/Http/Controllers/EditorialPreviewController.php`.
- Vistas: `resources/views/courses/editorial-preview-paged.blade.php`, `resources/views/courses/learn.blade.php`, `resources/views/courses/blocks/scenario.blade.php`.
- Antecedente: [Integración del formato por páginas](CURSOS-POR-PAGINAS-2026-09-24.md).
- Comparación ejecutada mediante script temporal `tmp/release-20260929/compare-editorial.php`, con PostgreSQL en modo de transacción predeterminado de solo lectura. Salida limitada a currículo e identificadores, sin datos de estudiantes.
- No se repitió el comprobador PowerShell editorial: la política local impidió ejecutarlo. No se cambió esa política. El resultado anterior de ese comprobador no se presenta como una ejecución nueva.
