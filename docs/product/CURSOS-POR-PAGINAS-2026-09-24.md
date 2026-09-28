# Integración del formato por páginas en cursos

La vista `courses.learn` compartida por estudiantes y la prueba administrativa presenta una página de preparación, una por bloque de contenido y una de cierre. La ficha de práctica, fuentes y apoyos quedan en un desplegable. Se conservan los contenidos por etapa, las restricciones de acceso y los identificadores originales.

La navegación usa `x-show`, no reconstruye las prácticas: las decisiones se conservan al avanzar y volver dentro de la misma visita. No se promete persistencia de respuestas parciales al recargar o cerrar. El envío de finalización y sus validaciones de servidor no cambian. La lectura en voz alta filtra páginas ocultas; el foco vuelve al indicador de página.

Las seis escenas de las primeras tres lecciones modelo usan la retroalimentación común y conservan el campo de respuesta cuando corresponde. Las cuatro situaciones de pasajeros compatibles se conectan al motor 3D ya probado, conservando opciones y textos publicados. El controlador transmite el identificador original al registro de decisiones y revoca el acierto al reintentar. Estas diez escenas se pausan al cambiar de página.

No se han reemplazado textos de la base de datos por el borrador editorial, ejecutado seeders, cambiado etapas ni otorgado validaciones curriculares. El nuevo formato no representa aprobación especializada.

Verificaciones: compilación de recursos; 29 pruebas de matrícula, vista editorial y finalización (486 aserciones); una prueba adicional de los diez campos de respuesta (54 aserciones); comprobación de eventos del pasajero y 1616 poses de las cuatro escenas.

Respaldo previo en servidor: `/tmp/edudrive-before-course-pages-20260924.tar.gz`. Verificación visual autenticada pendiente mientras la sesión de administración está vencida.
