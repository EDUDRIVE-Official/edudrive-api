# Consolidación del catálogo y recorrido de aprendizaje

Consulta de producción realizada el 20 de septiembre de 2026 (Costa Rica), sin modificar registros.

## Inventario verificado

24 cursos publicados, 130 lecciones. Las 130 contienen un diseño de aprendizaje con `stage=pending_review`. La presencia del diseño no equivale a clasificación por público ni a revisión pedagógica concluida. No se encontró el curso borrador P912 entre los cursos de esta consulta.

| Código | Curso | Lecciones |
|---|---|---:|
| EDU-EXP-001 | Misión Camino Seguro | 27 |
| EDU-EXP-002 | Misión Pedalea Visible | 15 |
| EDU-EXP-003 | Misión Pasajero Responsable | 12 |
| EDU-EXP-004 | Misión Motociclista Visible | 8 |
| EDU-EXP-005 | Misión Conducción Preventiva | 8 |
| EDU-EXP-006 | Misión Movilidad Sostenible | 6 |
| EDU-EXP-007 | Misión Entorno Escolar Seguro | 3 |
| EDU-EXP-008 | Misión Respuesta Segura ante Incidentes | 3 |
| EDU-EXP-009 | Misión Familia Vial | 3 |
| EDU-EXP-010 | Misión Movilidad Inclusiva | 3 |
| EDU-EXP-011 | Misión Visibilidad, Lluvia y Noche | 3 |
| EDU-EXP-012 | Misión Zonas de Obra y Rutas Cambiantes | 3 |
| EDU-EXP-013 | Misión Micromovilidad Segura | 3 |
| EDU-EXP-014 | Misión Caminos Rurales y Fauna | 3 |
| EDU-EXP-015 | Misión Comunidad Vial Activa | 3 |
| EDU-EXP-016 | Misión Tecnología y Movilidad Inteligente | 3 |
| EDU-EXP-017 | Misión Señales, Prioridades y Acuerdos | 3 |
| EDU-EXP-018 | Misión Movilidad Laboral Segura | 3 |
| EDU-EXP-019 | Misión Vehículo en Condiciones Seguras | 3 |
| EDU-EXP-020 | Misión Cruces Ferroviarios Seguros | 3 |
| EDU-EXP-021 | Misión Emociones y Autocontrol Vial | 3 |
| EDU-EXP-022 | Misión Vehículos Grandes y Puntos Ciegos | 3 |
| EDU-EXP-023 | Misión Estacionamientos y Maniobras Seguras | 3 |
| EDU-EXP-024 | Misión Viajes y Rutas Desconocidas | 3 |

## Cambio de este incremento

El catálogo muestra el público que realmente consta en las lecciones y su cobertura para administradores. Una recomendación por etapa exige curso publicado, no completado y todas sus lecciones clasificadas en una misma etapa coincidente con el perfil. Una combinación de etapas no demuestra que el curso completo sea apto para cada una. La falta de fecha de nacimiento, fecha futura o edad inferior a cinco años no se interpreta como público universal ni autonomía para practicar.

Las nuevas aprobaciones/publicaciones sujetas al control EDU-EXP rechazan etapa pendiente. Se conserva su alcance existente; no es una validación de todo el currículo. No se ejecutan seeders ni se reclasifican, despublican o reescriben cursos, certificados o avances históricos.

## Trabajo que sigue dentro del mismo sistema

1. Revisar las 130 lecciones: objetivo observable, complejidad del lenguaje, papel vial, etapa propuesta, acompañamiento, jurisdicción, calidad de actividad y evidencia. El título de un curso no basta para clasificarlo. Guardar propuesta por identificador de lección y versión antes de modificar contenido publicado.
2. Completar la revisión humana de las cuatro escenas y resolver hallazgos. Confirmar y crear el borrador P912 en producción cuando corresponda; una designación revisora no crea el curso ni integra animaciones en lecciones.
3. Incorporar las escenas revisadas al recorrido curricular, comprobar matrícula, práctica, retroalimentación y cierre desde una cuenta de estudiante; aclarar el alcance de certificados.
4. Ensayar con docente y luego realizar el piloto acompañado de 9–12 años. Registrar comprensión, ayudas, transferencia y carga docente antes de extender conclusiones.

Se puede presentar el sistema como plataforma funcional en consolidación y convocar aliados para el piloto. La publicación técnica de cursos y la entrega de invitaciones no acreditan eficacia educativa ni aprobación institucional.

## Verificación de este incremento

31 pruebas aprobadas, 151 aserciones: clasificación del catálogo, correspondencia con la edad, fechas desconocidas o fuera de alcance, matrícula y finalización, y publicación. Ejecutadas en contenedor sin red, código de solo lectura y SQLite en memoria; no se usó la base de producción para pruebas. El inventario anterior sí proviene de una consulta de solo lectura a producción.

Esta entrega corrige la orientación del catálogo. La revisión editorial de cada una de las 130 lecciones y su aprobación humana continúan pendientes; no se asignaron edades por suposición.
