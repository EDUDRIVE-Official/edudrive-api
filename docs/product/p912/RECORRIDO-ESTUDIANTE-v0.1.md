# Vista de estudiante — ensayo U01

Ruta: `/pilot-instruments/journey`, enlazada desde la guía docente. Solo local/testing, cuenta con `courses.manage` y confirmación de datos ficticios. No habilita cuentas de estudiantes.

## Secuencia

1. Diagnóstico: D01, D02, D04, una situación por página, sin pistas ni devolución automática.
2. Práctica: U01-A, U01-B, U01-C, escenas redactadas para el participante; pista opcional y explicación después de guardar la primera respuesta. Reintentos conservados.
3. Comprobación: C01, C02, C04, sin pistas automáticas ni modificación de primera respuesta.
4. Cierre: respuestas y ayudas declaradas, preguntas de reflexión y revisión con acompañante. No se infiere acierto, comprensión, dominio ni necesidad individual de refuerzo mediante clasificación automática.

La página solo recibe la situación actual. La evaluación futura y los criterios reservados no se envían al navegador. Para avanzar se guarda una respuesta, incluso en blanco; no se exige acertar. Se rechazan solicitudes fuera de orden, revisiones antiguas y pistas fuera de práctica. Bloqueo de sesión para serializar envíos simultáneos y token para distinguir el recorrido. El guardado de nuevo se permite únicamente en práctica, hasta diez intentos por situación.

## Persistencia y límites

El contenido se copia al iniciar, con versión de banco/unidad. Las respuestas se conservan en la sesión del administrador, no en tablas de evidencia, matrículas o Pasaporte. Pueden perderse al expirar/cerrar sesión. No existe reinicio destructivo ni exportación en esta versión. Recargar conserva el punto actual mientras la sesión exista.

El acompañante puede declarar ayuda de contenido; una respuesta con esa ayuda es formativa. La práctica siempre es formativa. Esta pantalla no captura aún todo el contexto de observación ni sustituye el registro docente. No usarla como instrumento de investigación ni con menores.

La secuencia puede ensayarse seguida para revisar su funcionamiento. Pedagógicamente, comprobación, transferencia y retención deben organizarse según la guía, en momentos distintos; esta versión no controla intervalos ni comprueba aplicación de T01 o seguimiento. Se mantiene alternativa textual; no se añadieron animaciones ni se alteraron cursos publicados.

## Revisión

Corrección a partir de observación del responsable: no quedaba claro qué intentaba hacer el personaje. Se añade propósito antes de cada escena: llegar al otro lado de la calle, aún desde la acera; o continuar por la acera cuando hay una barrera. Se explica que la actividad actual consiste en responder, no cruzar una vía real ni controlar una animación. Aclaración visible también en sesiones ya iniciadas, sin alterar sus respuestas ni el banco de criterios.

Inicio inspeccionado en navegador autenticado en modo oscuro. La prueba HTTP recorre las nueve situaciones y el cierre, revisa separación de tareas futuras, revelación de explicación y uso de pistas. El prototipo permanece sujeto a revisión docente/vial y pruebas completas de accesibilidad antes de cualquier aplicación real.
