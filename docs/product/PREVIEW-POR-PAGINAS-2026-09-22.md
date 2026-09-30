# Primera lección editorial por páginas

Alcance actualizado: las cinco lecciones de `/pilot-instruments/editorial-preview`, cada una con cinco páginas. No se modifican contenidos persistidos, matrículas, progreso ni certificados.

Recorrido con `lesson=1&page=1..5`:

1. Antes de decidir: contexto original, dos pistas, misión y ampliación opcional.
2. El atajo entre automóviles: escena existente, decisiones y conversación.
3. La esquina con poca visibilidad: escena existente, decisiones y conversación.
4. Mostrá lo que observaste: actividad original y apoyos para explicar.
5. Lo que te llevás: recapitulación y caso de transferencia original.

Navegación mediante enlaces normales (sin depender de JavaScript). Solo se renderiza una página, con indicador de posición, título, anterior/siguiente y ancla al contenido. La guía del acompañante y la selección de otras lecciones están plegadas. Las escenas conservan su propia retroalimentación junto a la decisión.

Esta vista no guarda respuestas: al volver a una página se reinicia su práctica. Se explica en el apartado «Sobre esta prueba». El indicador expresa posición, no aprobación. El cierre no certifica aprendizaje ni autoriza cruces autónomos.

Las lecciones 2–5 siguen la misma secuencia: introducción original con dos apoyos y ampliación opcional, una página por escenario, actividad original con tres apoyos y cierre con el caso de transferencia original. Los apoyos específicos se mantienen en `resources/curriculum/editorial/page-support.php`. Se conservaron los títulos, opciones y explicaciones de los escenarios. Las lecciones de pasajeros no anuncian práctica 3D: mantienen su representación básica y lo indican expresamente.

En el cierre se ofrece un enlace a la siguiente lección, reiniciando su página a 1. En la última se ofrece volver a cursos. El selector de lecciones marca la actual.

Verificación automatizada: `EditorialPreviewTest`, seis pruebas y 315 aserciones: permisos, las 25 páginas, aislamiento de escenas, contenido de apoyo específico, enlaces de navegación, aclaración de escenas básicas y parámetros inválidos.

La primera lección fue aceptada visualmente por el usuario. Pendiente: revisión del formato extendido y revisión especializada del contexto adicional antes de incorporarlo a cursos publicados.
