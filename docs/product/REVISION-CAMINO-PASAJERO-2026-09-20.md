# Revisión editorial: Camino Seguro y Pasajero Responsable

Fecha: 20 de septiembre de 2026. Estado: propuesta de revisión, NO aprobación pedagógica ni normativa.

## Resultado ejecutivo

Conviene consolidar lo construido, no abrir otro sistema. Los dos cursos tienen una base aprovechable: situaciones cotidianas, objetivos, retroalimentación, acompañamiento e inclusión. El problema principal es la alineación entre lo que se promete enseñar, lo que el estudiante hace y lo que realmente se registra.

Se revisaron las 39 lecciones completas de estos dos cursos en producción: objetivos/diseño y los 160 bloques, incluidos contextos, opciones, respuestas y actividades. No se declara revisado el contenido completo de las otras 91 lecciones. No se probaron aquí todas las animaciones ni todos los recorridos de usuario; eso requiere QA posterior. Las referencias normativas guardadas se inventariaron, pero sus fechas no son prueba de revisión externa efectiva.

La consulta fue de solo lectura. No se cambiaron cursos publicados, edades, matrículas, avances, certificados ni revisiones profesionales. La copia curricular que permite contrastar esta revisión está en [fuente consultada](review-camino-pasajero-source-2026-09-20.json).

## Hallazgos comprobados

| Evidencia | Camino Seguro | Pasajero Responsable |
|---|---:|---:|
| Lecciones | 27 | 12 |
| Bloques de texto | 54 | 24 |
| Escenarios de decisión | 58 | 24 |
| Respuesta correcta guardada en segunda posición | 57/58 | 24/24 |
| Etapa pendiente de revisión | 27/27 | 12/12 |

1. **Patrón de respuesta:** 81 de 82 escenarios tienen la opción correcta en segunda posición. Las plantillas locales inspeccionadas recorren las opciones en su orden. Rebalancear posiciones y mejorar distractores; si se introduce orden variable, conservar identificadores y verificar que las animaciones no dependan de A/B/C. No basta con mezclar preguntas.
2. **Plantilla excesiva en Pasajero:** los 24 escenarios comparten pregunta genérica, tercer distractor y retroalimentaciones genéricas. Reescribir por causa y consecuencia concreta. No atribuir a este banco capacidad de evaluar competencia independiente.
3. **Repetición sin progresión explícita:** señal favorable/giro, autobús que oculta y acera bloqueada reaparecen en distintas integradoras. Pueden servir para práctica espaciada, pero necesitan distinta dificultad, rol claro y un propósito declarado. Evitar repetir el mismo razonamiento como si fuera evidencia nueva.
4. **Rol ambiguo:** “Capas de información” y “Acuerdos que protegen” contienen lenguaje de conductor dentro de Camino Seguro. “Protección adecuada para cada cuerpo” evalúa instalación/historial de sistemas que corresponden al adulto responsable.
5. **Desalineación puntual:** “Planes que pueden cambiar” promete un imprevisto durante el cruce, pero sus dos escenarios suceden antes de iniciarlo. “Todos compartimos la vía” está vinculado a PEATON.LUGAR aunque pretende anticipar actores.
6. **Evidencia limitada:** el manejador local CompleteLessonHandler exige respuesta correcta para completar, registra resultados correctos y marca formative_completion / demonstrates_mastery=false. Es una distinción acertada. No usar esa finalización para afirmar dominio sin ayuda, retención o desempeño real. Esta observación no equivale a auditar todos los exámenes del sistema.
7. **No confundir bloque con presentación:** que los bloques sean text/scenario no significa que no haya imágenes. scenario.blade.php selecciona diversas escenas 3D por título. Se debe revisar coherencia, accesibilidad y correspondencia de cada animación, reutilizando lo existente.
8. **Riesgo de romper escenas al editar:** la selección visual por título exige preservar títulos vinculados o migrar a claves estables antes de renombrar. Una mejora editorial no debe hacer desaparecer la escena.
9. **Lenguaje y acompañamiento:** hay mezcla de tuteo/voseo, encabezados abstractos y prácticas “adecuadas para tu edad” sin condiciones suficientes. El piloto 9–12 debe especificar aula/maqueta, acompañamiento, materiales, criterio de observación y alternativa accesible. No implica prácticas de cruce real.
10. **Numeración:** “Reto final” no cierra el curso de 27 lecciones y la segunda unidad empieza con “Reto 2”. Usar cierres de bloque y secuencia inequívoca.

## Un solo sistema, distintas formas de participar

La propuesta no crea catálogos independientes por edad. Mantiene competencias, cursos, identificadores y Pasaporte compartidos. Lo que cambia es lenguaje, complejidad, papel vial, apoyo y evidencia esperada.

- 5–8: posible adaptación posterior mediante imágenes, una decisión por vez y mediación adulta; estos cursos completos NO quedan aprobados para esa edad.
- 9–12: primer recorrido a validar, centrado en peatón y pasajero acompañado. Las fichas siguientes proponen qué simplificar y qué observar.
- Jóvenes: reutilizar competencias con problemas de presión social, orientación y mayor complejidad, sin convertir edad en autorización de autonomía vial.
- Adultos: mismos fundamentos con responsabilidades explícitas de conducción, acompañamiento y protección de menores; sin infantilizar el lenguaje.
- Personas mayores o con necesidades de acceso: ofrecer modalidades equivalentes y apoyos según necesidad, no inferir incapacidad por edad ni excluir por no oír/ver una pista.
- Si falta información de perfil: explicar qué se necesita para orientar; nunca asumir público universal.

**Límite técnico actual:** un solo campo stage por lección no expresa “niño participa + adulto verifica instalación”. No asignar discover a todas las lecciones por comodidad. Primero decidir cómo presentar las instrucciones de acompañante dentro del mismo recorrido y cómo representar público/rol sin perder compatibilidad con catálogo y recomendaciones.

## Progresión propuesta dentro de los cursos existentes

No sustituye las 39 lecciones por seis pantallas ni elimina registros. Agrupa contenidos por función didáctica; las fichas completas abajo conservan trazabilidad.

| Momento | Acción del estudiante | Material existente prioritario | Evidencia propuesta |
|---|---|---|---|
| 1. Reconocer el espacio | Señalar lugar protegido y trayectorias | CS01, CS02, CS11 | Dos pistas y un lugar descartado |
| 2. Construir la decisión | Ordenar pausa, observación y comprobación | CS04, CS06, CS07 | Secuencia en simulación, no cruce real |
| 3. Cambiar el plan | Comparar ruta visible y barrera | CS03, CS05, CS13, CS19 | Explicación ante cambio no ensayado |
| 4. Viajar como pasajero | Esperar, subir, protegerse y bajar | PR01–PR04, PR07–PR09 | Rutina observada y tareas adultas diferenciadas |
| 5. Comunicar y cuidarse | Pausar distracción, avisar y pedir apoyo | CS22–CS26, PR05, PR11 | Ensayo de comunicación con ayudas registradas |
| 6. Integrar y transferir | Resolver situación nueva y justificar | CS09, CS15, CS21, CS27; PR12 guiada | Decisión inicial, explicación y seguimiento |

La duración se estimará tras ensayo docente; no hay medición que permita asegurar que 39 lecciones caben en seis sesiones. Las demás fichas son profundización/refuerzo dentro del mismo programa. El primer ensayo puede usar una selección acotada sin anunciar que se completó todo el curso.

## Reglas editoriales para el siguiente incremento

Cada lección debe tener una consigna breve, rol explícito, escena o representación útil, decisión observable, feedback específico y actividad de transferencia protegida. Una ilustración no reemplaza una actividad y una animación decorativa no acredita aprendizaje.

Reutilizar escenas revisadas de giro, van, descenso y barrera; verificar correspondencia antes de integrarlas. Dar control de pausa/repetición, opción de movimiento reducido y versión operable por teclado. No usar sonido o color como única pista ni premiar rapidez.

Para evaluación independiente: casos nuevos, primera decisión registrada, apoyos/reintentos separados y explicación breve. Propuesta de rúbrica por indicador: 0 = no identifica todavía; 1 = identifica con apoyo; 2 = decide y justifica sin pista. La rúbrica y cualquier umbral de aprobación requieren revisión docente y ensayo; no se fijan cortes de certificación arbitrarios.

Las propuestas de las fichas no son instrucciones médicas, criterios de instalación de retención ni dictámenes legales. Seguridad vial valida guiones y referencias específicas; accesibilidad valida alternativas; docencia valida carga, lenguaje y evidencia. Ninguna edad propuesta se escribe en producción hasta completar esa revisión.

## Fichas de revisión por lección

CS = Camino Seguro (EDU-EXP-001). PR = Pasajero Responsable (EDU-EXP-003).
Públicos y objetivos son propuestas editoriales, no clasificación aprobada. Todas las variantes de primaria mantienen acompañamiento y práctica fuera del tránsito.

### CS01. Reto 1: ¿Por dónde cruzamos?

- Identificador: `e67850f8-00c0-5583-b087-157e7f92813e`; versión consultada: 2; unidad: 1. Encuentra el lugar seguro.
- Objetivo actual: Elegir un lugar de cruce señalizado y visible.
- Público/uso propuesto: 9–12; inicio.
- Objetivo observable propuesto: Seleccionar un lugar y justificarlo con dos pistas visibles.
- Ajuste: Conservar ambos escenarios; mostrar el campo visual, no presentar el paso marcado como garantía. Distinguir elegir lugar de decidir cuándo cruzar.
- Evidencia propuesta: Marca dos pistas en una escena nueva y explica por qué descarta el atajo.

### CS02. Reto 2: Todos compartimos la vía

- Identificador: `dc052328-04a0-5e0e-8118-eab46b3a5cf7`; versión consultada: 2; unidad: 1. Encuentra el lugar seguro.
- Objetivo actual: Reconocer actores viales y anticipar sus movimientos.
- Público/uso propuesto: 9–12; inicio.
- Objetivo observable propuesto: Identificar dos actores y sus posibles trayectorias.
- Ajuste: El objetivo es reconocer actores pero el indicador es PEATON.LUGAR. Revisar esa alineación; usar la escena del autobús como introducción, no repetir luego la misma pregunta.
- Evidencia propuesta: Señala ambos movimientos en garaje/bicicleta, con alternativa textual.

### CS03. Reto 3: Una ruta segura para todas las personas

- Identificador: `11a8de85-50f4-5f65-acbc-42d0a621ad80`; versión consultada: 2; unidad: 1. Encuentra el lugar seguro.
- Objetivo actual: Evaluar accesibilidad, visibilidad y protección al elegir una ruta.
- Público/uso propuesto: 9–12; aplicación.
- Objetivo observable propuesto: Comparar rutas y explicar una barrera de accesibilidad.
- Ajuste: Sustituir «demuestra ciudadanía vial» por una pregunta concreta. Añadir símbolos además de colores al mapa; pedir apoyo adulto sin encargar al niño resolver la barrera.
- Evidencia propuesta: Elige ruta y explica visibilidad, continuidad y accesibilidad.

### CS04. Reto 2: Pausa de detective

- Identificador: `40b3b0ca-39f5-53f6-b9e6-cd1aed3d5853`; versión consultada: 2; unidad: 2. Detente, mira y escucha.
- Objetivo actual: Detenerse, mirar y escuchar antes de cruzar.
- Público/uso propuesto: 9–12; inicio.
- Objetivo observable propuesto: Detenerse antes del borde y comprobar trayectorias.
- Ajuste: Usar aquí la enseñanza inicial del giro con verde. En el ensayo no premiar solo obedecer la señal del acompañante: pedir qué observó. Ofrecer alternativas a las consignas auditivas.
- Evidencia propuesta: Ordena pasos e identifica el carril todavía oculto.

### CS05. Reto 3: Cuando el entorno cambia

- Identificador: `e0a2572f-11b3-5aa7-ab7b-9e7041a24cda`; versión consultada: 2; unidad: 2. Detente, mira y escucha.
- Objetivo actual: Adaptar la observación ante lluvia, oscuridad y distracciones.
- Público/uso propuesto: 9–12; aplicación.
- Objetivo observable propuesto: Reevaluar el cruce cuando pierde visibilidad.
- Ajuste: Reducir solapamiento con «Cuando cambia el entorno»: aquí observar con lluvia; allá decidir cambio de ruta. No depender de contacto visual como prueba.
- Evidencia propuesta: Explica qué información dejó de estar disponible y por qué espera.

### CS06. Reto 4: Velocidad, distancia y tiempo

- Identificador: `ccee7b09-0c3c-54a7-a9d4-88849b852bb3`; versión consultada: 2; unidad: 2. Detente, mira y escucha.
- Objetivo actual: Comprender que la distancia aparente no garantiza tiempo suficiente para cruzar.
- Público/uso propuesto: 9–12; profundización.
- Objetivo observable propuesto: Rechazar una decisión que exige competir con un vehículo.
- Ajuste: Cambiar «comprender» por acción observable. Mantener maqueta, sin pruebas de estimación de huecos en tránsito real ni cronómetro competitivo.
- Evidencia propuesta: Compara dos movimientos de juguete y justifica no correr.

### CS07. Reto final: completa la misión

- Identificador: `d4c9a22d-2458-549b-abf0-8af8535fdf4a`; versión consultada: 2; unidad: 3. Cruza con calma.
- Objetivo actual: Aplicar la secuencia completa de cruce seguro.
- Público/uso propuesto: 9–12; integración.
- Objetivo observable propuesto: Aplicar la secuencia de cruce en simulación.
- Ajuste: El nombre «Reto final» aparece antes de muchas lecciones: renombrar como cierre del bloque. Delimitar escena sin isla y trayectorias; revisión vial del guion antes de generalizar su respuesta.
- Evidencia propuesta: Ordena secuencia completa y resuelve incidente del objeto sin copiar un caso previo.

### CS08. Reto experto: planes que pueden cambiar

- Identificador: `03585fbb-600e-5cbd-82aa-76c9ca9d34fa`; versión consultada: 2; unidad: 3. Cruza con calma.
- Objetivo actual: Responder con seguridad cuando aparece un imprevisto durante el cruce.
- Público/uso propuesto: 9–12; profundización.
- Objetivo observable propuesto: Actualizar el plan antes de iniciar el cruce.
- Ajuste: Objetivo dice «durante el cruce», pero ambos escenarios ocurren antes. Alinear objetivo con escenarios, o desarrollar un caso durante el cruce revisado por especialista. No duplicar verde/giro de la pausa.
- Evidencia propuesta: Distingue dónde está el peatón cuando aparece información nueva.

### CS09. Misión integradora: explicá tu decisión

- Identificador: `d8b4e5ed-cc94-5bb1-a974-43300460df62`; versión consultada: 2; unidad: 3. Cruza con calma.
- Objetivo actual: Justificar y transferir una decisión completa de cruce seguro.
- Público/uso propuesto: 9–12; integración.
- Objetivo observable propuesto: Justificar una decisión nueva con pistas, descarte y acción.
- Ajuste: Tres elecciones no demuestran por sí solas justificar/transferir. Añadir explicación y rúbrica. Precisar práctica en aula o maqueta, no una orden abierta de cruzar.
- Evidencia propuesta: Decisión inicial sin ayuda, explicación y revisión después de feedback.

### CS10. Reto 1: Lee las pistas

- Identificador: `79e87bb8-61cf-5350-a78c-bf7a94067736`; versión consultada: 2; unidad: 1. Activa tu radar.
- Objetivo actual: Distinguir un peligro de la posibilidad de sufrir daño.
- Público/uso propuesto: 9–12; profundización.
- Objetivo observable propuesto: Relacionar una pista, una persona expuesta y una consecuencia posible.
- Ajuste: Sustituir definiciones abstractas iniciales por imagen y ejemplo; simplificar barrido de seis preguntas. Mantener detalle técnico de adherencia como apoyo docente.
- Evidencia propuesta: Señala pista y posible movimiento sin exigir vocabulario técnico.

### CS11. Reto 2: Lo que no se ve

- Identificador: `56e76de7-a1f3-5e89-abc8-fcf2b2410eee`; versión consultada: 2; unidad: 1. Activa tu radar.
- Objetivo actual: Reconocer puntos ciegos y obstáculos que esconden movimiento.
- Público/uso propuesto: 9–12; inicio.
- Objetivo observable propuesto: Reconocer una zona que no puede observar.
- Ajuste: Cambiar encabezado abstracto «ausencia de evidencia». En caso rural explicitar qué hacer si no existe punto protegido o alternativa visible: solicitar apoyo, sin inventar camino.
- Evidencia propuesta: Muestra en maqueta qué queda oculto y propone no exponerse.

### CS12. Reto 3: ¿Qué podría pasar después?

- Identificador: `b60cba9d-635e-50e4-a791-8b5f3980aa3c`; versión consultada: 2; unidad: 2. Piensa unos segundos adelante.
- Objetivo actual: Anticipar movimientos probables de distintos actores viales.
- Público/uso propuesto: 9–12; profundización.
- Objetivo observable propuesto: Anticipar un movimiento sin tratarlo como certeza.
- Ajuste: Diferenciar pista de confirmación. Aclarar rol del estudiante en fila escolar; no exigir estimación de probabilidad formal.
- Evidencia propuesta: Nombra una posibilidad y una razón por la que podría equivocarse.

### CS13. Reto 4: Cuando cambia el entorno

- Identificador: `c2e3cb10-cf2b-5389-b7c3-101f862b62d0`; versión consultada: 2; unidad: 2. Piensa unos segundos adelante.
- Objetivo actual: Replantear la decisión ante lluvia, oscuridad, ruido o tránsito intenso.
- Público/uso propuesto: 9–12; aplicación.
- Objetivo observable propuesto: Cambiar una ruta al cambiar sus condiciones.
- Ajuste: Reservar lluvia/observación a CS05; aquí comparar planes. Semáforo personal con palabras e iconos, no solo colores.
- Evidencia propuesta: Elige plan alternativo en apagón y explica qué cambió.

### CS14. Reto 5: Tiempo, espacio y salida

- Identificador: `eee86a12-4d62-523d-8b0a-f9c4234efe2d`; versión consultada: 2; unidad: 3. Elige con margen.
- Objetivo actual: Elegir alternativas que toleren errores y cambios inesperados.
- Público/uso propuesto: 9–12; aplicación.
- Objetivo observable propuesto: Descartar una opción sin espacio de reacción.
- Ajuste: Representar margen con espacio visible y no solo definición; enlazar pérdida del bus con plan de apoyo para menores.
- Evidencia propuesta: Compara dos planes e identifica cuál depende de correr o confiar.

### CS15. Misión integradora: cambia el plan

- Identificador: `aace6090-9063-515c-a558-2a15812678e0`; versión consultada: 2; unidad: 3. Elige con margen.
- Objetivo actual: Integrar detección, anticipación, margen y replanteamiento.
- Público/uso propuesto: 9–12; integración.
- Objetivo observable propuesto: Detectar cambio y reformular el plan.
- Ajuste: Repite bus, obra y emergencia ya enseñados. Convertir en transferencia con configuración distinta y sin respuesta ensayada; concretar «práctica adecuada a tu edad».
- Evidencia propuesta: Justifica un cambio en caso nuevo con rúbrica común.

### CS16. Reto 1: No memoricés, interpretá

- Identificador: `96d2e1db-e341-5048-b99f-777db6961eaa`; versión consultada: 2; unidad: 1. La vía nos habla.
- Objetivo actual: Clasificar señales por la decisión que ayudan a tomar.
- Público/uso propuesto: 9–12; inicio.
- Objetivo observable propuesto: Relacionar señal visible y decisión concreta.
- Ajuste: Mostrar señales reales pertinentes a la jurisdicción; no solo decir que hay una señal desconocida. Validar cada imagen y significado antes de publicar.
- Evidencia propuesta: Relaciona tres señales con acciones y explica una.

### CS17. Reto 2: Capas de información

- Identificador: `f61aff2d-87d6-590f-af4e-d55c78d0023d`; versión consultada: 2; unidad: 1. La vía nos habla.
- Objetivo actual: Combinar semáforos, marcas viales y entorno sin atender una sola pista.
- Público/uso propuesto: 9–12; profundización.
- Objetivo observable propuesto: Distinguir señal peatonal, vehicular y trayectoria.
- Ajuste: Primer escenario parece poner al alumno como conductor («señal sonora»); fijar rol peatonal en la variante infantil. Reducir cinco capas simultáneas a progresión guiada.
- Evidencia propuesta: Identifica qué señal le corresponde y qué movimiento falta comprobar.

### CS18. Reto 3: Tener prioridad y seguir cuidándote

- Identificador: `b7fcaf04-a4c5-5807-934f-9287c471f712`; versión consultada: 2; unidad: 2. Prioridad no significa invulnerabilidad.
- Objetivo actual: Distinguir el derecho de paso de la comprobación de seguridad.
- Público/uso propuesto: 9–12; aplicación.
- Objetivo observable propuesto: Diferenciar prioridad y comprobación del entorno.
- Ajuste: Mantener derechos y responsabilidad de conductores; evitar que autoprotección se lea como traslado de culpa al peatón. Verificación normativa específica del cruce de ciclovía.
- Evidencia propuesta: Explica qué organiza la norma y qué todavía necesita observar.

### CS19. Reto 4: Cuando la cortesía confunde

- Identificador: `9d444e51-14db-528d-8057-0aef334f6a90`; versión consultada: 2; unidad: 2. Prioridad no significa invulnerabilidad.
- Objetivo actual: Evaluar gestos informales sin asumir que controlan toda la vía.
- Público/uso propuesto: 9–12; aplicación.
- Objetivo observable propuesto: No confundir un gesto con control de todos los carriles.
- Ajuste: Concentrar aquí el caso de cortesía, ya presente en la integradora. Mostrar el carril oculto y evitar repetir preguntas equivalentes seguidas.
- Evidencia propuesta: Señala qué trayectoria no controla la persona que hace el gesto.

### CS20. Reto 5: La vía también es de otras personas

- Identificador: `63589073-6cdf-5149-8016-17fa7d1695e4`; versión consultada: 2; unidad: 3. Movimientos que otras personas comprenden.
- Objetivo actual: Reconocer necesidades distintas y evitar conductas que excluyen.
- Público/uso propuesto: 9–12; convivencia.
- Objetivo observable propuesto: Ofrecer ayuda con consentimiento y no bloquear el paso.
- Ajuste: Conservar enfoque inclusivo. Diferenciar ofrecer ayuda de asumir conducción de otra persona; canalizar intervención física al adulto responsable.
- Evidencia propuesta: Ensayo verbal de oferta y reorganización de un espacio simulado.

### CS21. Misión integradora: acuerdos que protegen

- Identificador: `03542bc2-432c-5e55-a01d-4fdcafa939c7`; versión consultada: 2; unidad: 3. Movimientos que otras personas comprenden.
- Objetivo actual: Integrar señales, prioridades, comunicación e inclusión.
- Público/uso propuesto: 9–12; integración con revisión de rol.
- Objetivo observable propuesto: Integrar señales, convivencia y replanteamiento.
- Ajuste: El caso del apagón usa «reducís velocidad» y seguir un vehículo: mezcla rol conductor con peatón. Corregir perspectiva; despejar ruta comunitaria debe ser tarea adulta, no mover barreras por iniciativa infantil.
- Evidencia propuesta: Explica decisión peatonal y a quién pedir intervención sobre una barrera.

### CS22. Reto 1: Una atención, muchas demandas

- Identificador: `36dddfdc-97ca-563e-a9e1-c459f7975d9f`; versión consultada: 2; unidad: 1. Tu atención tiene límites.
- Objetivo actual: Reconocer tareas que compiten con la observación vial.
- Público/uso propuesto: 9–12; autocuidado.
- Objetivo observable propuesto: Separar uso de pantalla y desplazamiento.
- Ajuste: No presuponer que cada niño tiene teléfono; ofrecer mapa de papel/juguete como alternativa. Animación debe detener al personaje en zona protegida antes de mirar.
- Evidencia propuesta: Ordena pausa, orientación, guardar y reevaluar.

### CS23. Reto 2: Escuchar también orienta

- Identificador: `20b42671-6ffc-5570-822d-20731414b503`; versión consultada: 2; unidad: 1. Tu atención tiene límites.
- Objetivo actual: Gestionar audífonos, ruido y conversaciones en zonas de decisión.
- Público/uso propuesto: 9–12; autocuidado; revisión de accesibilidad.
- Objetivo observable propuesto: Recuperar atención y reconocer información faltante.
- Ajuste: Distinguir audífonos de ocio de ayudas auditivas; no exigir retirar dispositivos de apoyo. El sonido no debe ser requisito para aprobar. Revisar ruido y alternativa visual con accesibilidad.
- Evidencia propuesta: Resuelve sin audio e identifica la zona oculta por autobús u obra.

### CS24. Reto 3: Tu semáforo interior

- Identificador: `eaf162c5-52c3-5af5-985d-99f171d028ef`; versión consultada: 2; unidad: 2. Cómo llegás también importa.
- Objetivo actual: Reconocer señales personales que requieren detenerse o pedir apoyo.
- Público/uso propuesto: 9–12; autocuidado.
- Objetivo observable propuesto: Reconocer una señal de prisa/enojo y pedir pausa.
- Ajuste: Conservar historia; animar pausa protegida, no cruce impulsivo premiado. Evitar interpretación clínica; no exigir relatos personales sensibles.
- Evidencia propuesta: Identifica señal del personaje y propone pausa o apoyo.

### CS25. Reto 4: Cuando el cuerpo pide margen

- Identificador: `b2e5dd3c-c152-5925-9fcb-972ad2f311e3`; versión consultada: 2; unidad: 2. Cómo llegás también importa.
- Objetivo actual: Modificar el plan ante cansancio o urgencia.
- Público/uso propuesto: 9–12 con acompañante; contenido adulto contextual.
- Objetivo observable propuesto: Elegir un plan alternativo ante cansancio o urgencia.
- Ajuste: Segundo escenario asigna responsabilidad a una persona adulta. Mostrarlo como conversación con acompañante, no evaluar al niño por gestionar fatiga adulta.
- Evidencia propuesta: Diferencia qué puede comunicar el niño y qué resuelve su acompañante.

### CS26. Reto 5: Mi decisión no necesita aplausos

- Identificador: `ebc3516b-3ed0-5f78-888c-72efd877bb46`; versión consultada: 2; unidad: 3. Elegir aunque otras personas presionen.
- Objetivo actual: Mantener límites seguros ante presión o burla.
- Público/uso propuesto: 9–12; comunicación.
- Objetivo observable propuesto: Comunicar un límite o advertir un peligro.
- Ajuste: Para primaria, revisar «nos vemos al otro lado» para no normalizar separación del acompañante/grupo responsable. Especificar que Luna sigue en acera al advertir a la persona adulta.
- Evidencia propuesta: Ensaya una frase de aviso y un acuerdo de espera sin exposición.

### CS27. Misión integradora: recuperá el control

- Identificador: `c9366df8-3fee-5e4d-9c14-8c858fef24ec`; versión consultada: 2; unidad: 3. Elegir aunque otras personas presionen.
- Objetivo actual: Integrar atención, estado personal, pausa y respuesta a la presión.
- Público/uso propuesto: 9–12; integración; extensión ciclista opcional.
- Objetivo observable propuesto: Reconocer estado, pausar y reconstruir plan.
- Ajuste: El caso de bicicleta cambia el rol del curso; reservar como transferencia explícita, no requisito inicial de peatón. No afirmar que práctica y reflexión quedaron registradas sin comprobar su captura.
- Evidencia propuesta: Caso peatonal nuevo más reflexión breve, registrando ayudas.

### PR01. Subir desde un lugar protegido

- Identificador: `10186544-ae2b-5ed0-8960-589af90689fc`; versión consultada: 1; unidad: 1. El viaje empieza antes de subir.
- Objetivo actual: Esperar hasta que el vehículo esté detenido y acceder desde el lado protegido.
- Público/uso propuesto: 9–12; pasajero inicial.
- Objetivo observable propuesto: Esperar detención y reconocer lado protegido.
- Ajuste: Precisar vehículo y persona adulta responsable de recolocarlo. Reescribir las tres retroalimentaciones y el distractor genérico. Ensayo sin motor/movimiento y control adulto.
- Evidencia propuesta: Ordena acceso y explica por qué no camina junto al vehículo.

### PR02. Bajar y volver a observar

- Identificador: `b9bd70ec-62b8-553f-a4f4-461f264453e2`; versión consultada: 1; unidad: 1. El viaje empieza antes de subir.
- Objetivo actual: Descender sin aparecer de repente ante otras personas.
- Público/uso propuesto: 9–12; puente pasajero–peatón.
- Objetivo observable propuesto: Separar descenso, espera protegida y decisión de cruce.
- Ajuste: Mostrar descenso completo hasta acera; no hacer del alejamiento del bus autorización automática para cruzar. Integrar escena revisada de descenso; no repetir idéntico caso de Camino.
- Evidencia propuesta: Reconoce cambio de rol y vuelve a comprobar en escena diferente.

### PR03. Cinturón durante todo el recorrido

- Identificador: `06204783-3523-587f-a6b8-5d0ea11b6b76`; versión consultada: 1; unidad: 2. Protección durante todo el viaje.
- Objetivo actual: Comprobar que el cinturón quede ajustado y permanezca colocado.
- Público/uso propuesto: 9–12; práctica con adulto.
- Objetivo observable propuesto: Detectar cinturón torcido o incómodo y pedir ayuda.
- Ajuste: Revisar ilustración/ajuste con especialista y fabricante, sin hacer responsable al niño de certificarlos. «Hasta detenerse» requiere aclarar fin de viaje, no cualquier semáforo.
- Evidencia propuesta: Reconoce problema en ilustración y comunica necesidad antes de salir.

### PR04. Protección adecuada para cada cuerpo

- Identificador: `a024c611-7f44-58fa-9101-0b0ac85824ea`; versión consultada: 1; unidad: 2. Protección durante todo el viaje.
- Objetivo actual: Reconocer que niñas y niños necesitan un sistema acorde con sus características.
- Público/uso propuesto: Adulto responsable; participación guiada 9–12.
- Objetivo observable propuesto: Diferenciar pedir protección de verificar instalación.
- Ajuste: La selección, historial e instalación del sistema son tarea adulta. Mantener dentro del mismo viaje con sección para acompañante; no reclasificar todo el curso como infantil ni exigir al niño decidir compatibilidad.
- Evidencia propuesta: Niño identifica que necesita ayuda; adulto registra revisión según fuente aplicable.

### PR05. Acompañar sin distraer

- Identificador: `fa9eeb50-699e-547f-9540-f15b8d74c9db`; versión consultada: 1; unidad: 3. Ayudar sin distraer.
- Objetivo actual: Evitar acciones que quitan atención o control a quien conduce.
- Público/uso propuesto: 9–12; convivencia.
- Objetivo observable propuesto: Distinguir distracción de aviso necesario.
- Ajuste: Cambiar «detenido» por detención segura fuera de circulación al hablar de mostrar un video; una luz roja no equivale a terminar conducción. Feedback específico sobre botella y pantalla.
- Evidencia propuesta: Clasifica una petición cotidiana y un aviso que no puede esperar.

### PR06. Espacio y respeto dentro del vehículo

- Identificador: `09663a3e-e015-5934-a7f4-cb924b6659c1`; versión consultada: 1; unidad: 3. Ayudar sin distraer.
- Objetivo actual: Ubicar cuerpo y pertenencias sin afectar a otras personas.
- Público/uso propuesto: 9–12; convivencia.
- Objetivo observable propuesto: Ubicar objetos sin obstruir y respetar el tiempo de otros.
- Ajuste: Coordinar con «Pertenencias bajo control»: aquí organización antes de salir; allí incidente durante viaje. Añadir imagen y evitar requerir recoger objetos en marcha.
- Evidencia propuesta: Distribuye objetos en plano y explica una obstrucción.

### PR07. Una espera segura y visible

- Identificador: `15db565c-b96d-5fc8-a873-777a652c7301`; versión consultada: 1; unidad: 1. Esperar sin exponerse.
- Objetivo actual: Elegir dónde esperar sin invadir la calzada.
- Público/uso propuesto: 9–12; transporte acompañado.
- Objetivo observable propuesto: Identificar espacio de espera sin invadir trayectorias.
- Ajuste: Concretar acompañamiento y alternativa cuando parada carece de protección; no dar por hecho que iluminación sola basta. Sustituir distractor genérico.
- Evidencia propuesta: Selecciona zona en escena y dice cuándo pedir ayuda.

### PR08. Subir sin empujar ni quedar atrapado

- Identificador: `3bf6846f-0228-565c-aa2c-d7265a0acd91`; versión consultada: 1; unidad: 1. Esperar sin exponerse.
- Objetivo actual: Abordar después del descenso y conservar apoyos.
- Público/uso propuesto: 9–12; transporte acompañado.
- Objetivo observable propuesto: Esperar descenso y no interferir con cierre de puertas.
- Ajuste: Mostrar manos/mochila fuera del cierre, orden claro y rol del adulto si pierde el bus. No simular atrapamientos reales.
- Evidencia propuesta: Ordena fila, descenso y ascenso con tarjetas o maqueta.

### PR09. Estabilidad durante el recorrido

- Identificador: `9c2c788e-8bc5-5145-92e5-54f9741a52b5`; versión consultada: 1; unidad: 2. Estabilidad y convivencia.
- Objetivo actual: Usar asiento o apoyo y anticipar cambios de movimiento.
- Público/uso propuesto: 9–12; revisión vial y accesibilidad previa.
- Objetivo observable propuesto: Reconocer falta de estabilidad y pedir apoyo.
- Ajuste: No generalizar viaje de pie a todo tipo de transporte escolar ni exigir ceder asiento ignorando necesidades propias no visibles. Revisar contexto de vehículo y normativa; simulación sin empujones ni desequilibrio inducido.
- Evidencia propuesta: Reconoce apoyo ausente sin juzgar necesidades por apariencia.

### PR10. Pertenencias bajo control

- Identificador: `75284ee2-7039-5cf5-a34d-c79b2c6a8c19`; versión consultada: 1; unidad: 2. Estabilidad y convivencia.
- Objetivo actual: Evitar que objetos bloqueen, distraigan o se conviertan en proyectiles.
- Público/uso propuesto: 9–12; aplicación pasajero.
- Objetivo observable propuesto: Responder a objeto caído sin exponerse.
- Ajuste: Diferenciar de organización inicial; contextualizar vehículo y detención segura. Para el bulto grande, la decisión de transporte corresponde al adulto.
- Evidencia propuesta: Explica qué avisa, qué no intenta recuperar y quién ayuda.

### PR11. Cuando la parada no es la esperada

- Identificador: `987d4351-cc96-5522-8dba-9833323827e8`; versión consultada: 1; unidad: 3. Cuando el plan cambia.
- Objetivo actual: Pedir información y apoyo sin abandonar un lugar seguro.
- Público/uso propuesto: 9–12 con acompañante; extensión adolescente.
- Objetivo observable propuesto: Activar el plan de apoyo ante desvío o parada perdida.
- Ajuste: Definir quién es personal responsable y alternativa sin teléfono. No registrar contactos reales del niño en respuestas; usar roles ficticios.
- Evidencia propuesta: Ensayo verbal: qué ocurrió, a quién aviso y dónde espero con apoyo.

### PR12. Calma, indicaciones y ayuda

- Identificador: `b09358bf-8e14-5837-a223-f9eee3bbb758`; versión consultada: 1; unidad: 3. Cuando el plan cambia.
- Objetivo actual: Responder ante una detención inesperada o emergencia sin crear otro riesgo.
- Público/uso propuesto: 9–12; instrucción guiada con revisión especializada.
- Objetivo observable propuesto: Avisar y seguir un procedimiento de apoyo en simulación.
- Ajuste: No presentar el curso como entrenamiento de rescate/primeros auxilios. Delimitar escenario de evacuación, tareas adultas y revisión del protocolo; no pedir al niño manipular personas lesionadas.
- Evidencia propuesta: Ordena acciones de aviso y apoyo con escenas no gráficas, sin simulacro peligroso.

## Camino de salida: presentar, ensayar y abrir

Primer incremento editorial preparado: [dos bloques reescritos, cinco lecciones](BORRADOR-BLOQUES-CAMINO-PASAJERO-v1.md). Es un borrador local con contenido completo y verificación estructural, no un cambio publicado ni una aprobación.

**Presentación a interesados:** se puede preparar una demostración de plataforma funcional y propuesta de piloto, mostrando con honestidad qué está validado y qué no. No afirmar acreditación, eficacia demostrada ni preparación nacional. La demostración debe recorrer experiencia de estudiante, apoyo docente y evidencia, no solo paneles administrativos.

**Antes del ensayo acompañado:** resolver los hallazgos críticos del recorrido seleccionado (rol, patrón de respuestas, consignas y guiones), obtener revisión humana, comprobar cuenta/matrícula/actividad/cierre y preparar docente, consentimiento y protección de datos según el protocolo aplicable. No simular firmas o aprobaciones.

**Antes de apertura amplia:** revisar las 91 lecciones restantes, probar recorridos por perfil, disponer de evidencia del piloto y corregir incidencias. La expansión no depende de añadir más cursos sino de demostrar que se entiende, se puede usar y se aprende lo definido.

### Orden concreto de trabajo

1. Corregir editorialmente un bloque completo de Camino Seguro y el bloque de acceso/descenso de Pasajero, como borrador versionado.
2. Rebalancear el banco de 82 escenarios y sustituir los 24 distractores/feedback genéricos de Pasajero. Mantener IDs de elecciones o migración explícita.
3. Alinear objetivos e indicadores; diferenciar instrucciones del estudiante y acompañante sin duplicar el sistema.
4. Revisar con los especialistas reales y hacer QA del recorrido seleccionado, incluidas las escenas existentes.
5. Ensayar con docente; registrar comprensión, apoyos, carga y fallos. Luego piloto 9–12 y seguimiento.
6. Extender la misma revisión al resto del catálogo; adaptar jóvenes/adultos a partir de necesidades verificadas.

No se ejecutan seeders sobre cursos publicados. Toda futura actualización debe comparar versión de origen, respaldar contenido, preservar IDs/historial y ofrecer reversión. Los cambios de esta entrega son documentos de revisión, no despliegue de contenido.

## Referencias de orientación (no homologación)

La recomendación de integrar educación con infraestructura, regulación y protección —sin hacer responsable al niño de compensar todo el sistema— es coherente con el [manual de seguridad peatonal de la OMS](https://www.who.int/publications/b/65858). La participación de docentes y familias en el aprendizaje práctico figura en la [orientación de la Comisión Europea sobre infancia](https://road-safety.transport.ec.europa.eu/eu-road-safety-policy/priorities/safe-road-use/children_en). Las decisiones curriculares específicas de este informe son propuestas propias basadas en el contenido inspeccionado, no exigencias atribuidas a esas entidades.

## Verificación y alcance final

- Cobertura de esta revisión: 39 identificadores únicos, 39 fichas, 160 bloques y 82 escenarios.
- Alcance restante: 91 lecciones del inventario de 130, revisión humana, validación normativa puntual, pruebas visuales completas y prueba de aprendizaje.
- No se cambiaron datos ni código de ejecución; no se presentan pruebas de software anteriores como validación de esta propuesta pedagógica.
