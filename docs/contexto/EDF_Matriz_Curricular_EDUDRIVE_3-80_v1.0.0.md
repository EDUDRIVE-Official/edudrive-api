# EDF — Matriz Curricular EDUDRIVE 3–80

Arquitectura curricular de referencia para Costa Rica

Versión 1.0.0  |  25 de septiembre de 2026  |  Identificador EDF-MC-3-80

EDUDRIVE organiza el aprendizaje vial como una progresión de competencias demostrables durante toda la vida. Este documento define qué debe poder hacer cada persona, con qué evidencia se comprueba, cómo progresa y qué puede afirmar su Pasaporte Vial.

| Etapa | Edad | Propósito |
| --- | --- | --- |
| DESCUBRO | 3–6 | Reconocer y actuar con acompañamiento |
| COMPRENDO | 7–12 | Comprender riesgos y preparar autonomía contextual |
| DECIDO | 13–16 | Decidir, anticipar y asumir responsabilidad |
| CONDUZCO | 17+ | Conducir según habilitación y mantener movilidad segura |

La matriz contiene 50 desempeños de etapa: 44 forman la progresión de 11 dominios y 6 amplían la formación de conductores, motociclistas y el aprendizaje permanente. Cada desempeño dispone de 16 campos curriculares, tres subcompetencias observables y una condición crítica de seguridad.

Destinatarios: dirección curricular, docentes, instructores, diseño de experiencias y responsables de Learning OS, SIMUDRIVE, Competency Trust Model y Pasaporte Vial.

Estado documental: arquitectura curricular definida para revisión y adopción por EDUDRIVE. No se presume aprobación institucional, acreditación estatal ni validación empírica de los umbrales. Las reglas de evaluación son decisiones de diseño EDUDRIVE de esta versión.

Alcance de esta entrega: especificación curricular y contratos conceptuales entre componentes. La implementación de edudrive-api queda fuera de esta versión.

# Guía de uso y autoridad del documento

## Cómo consultar la matriz

Leer primero las reglas comunes de progresión y evaluación. Después seleccionar el desempeño por código, etapa y rol. Las fichas son una vista legible de la matriz: conservan los 15 campos solicitados y añaden la ruta de formación, sin comprimirlos en una tabla de 16 columnas. El anexo estructurado contiene las mismas 50 filas para filtrar o transformar posteriormente.

| Bloque | Páginas | Contenido |
| --- | --- | --- |
| Fundamentos | 3–6 | Alcance, Costa Rica, etapas y progresión |
| Evaluación | 7–9 | Rúbrica, protocolos y confianza |
| Integración | 10–12 | Learning OS, SIMUDRIVE, CTM y Pasaporte |
| Gobernanza | 13 | Responsabilidades y control de cambios |
| Comparación | 14–19 | 18 unidades principales de THINK! |
| Matriz | 20–44 | 50 fichas curriculares detalladas |
| Fuentes | 45–46 | Referencias y registro de versión |

## Fuente curricular de verdad

La autoridad curricular es la versión publicada de EDF-MC-3-80 con su matriz integrada. La fuente editable en Markdown y el anexo JSON se generan de un mismo registro de edición; el PDF es la publicación de lectura. Las vistas derivadas no se editan por separado. Toda discrepancia exige corregir la edición y volver a publicar los tres archivos con la misma versión.

Orden de precedencia: normativa costarricense vigente para obligaciones legales; reglas normativas de este EDF para evaluación; ficha de competencia para el desempeño; plan de lección o recurso como realización didáctica. Un juego, curso o escenario no puede cambiar por sí solo un criterio de dominio.

## Cómo leer las referencias

T01–T18 identifican unidades oficiales THINK!; CR01–CR03 identifican fuentes costarricenses. La asociación con THINK! significa afinidad conceptual, no equivalencia literal ni aval británico. Los objetivos, juegos, protocolos P1–P4, códigos, escenarios y reglas de confianza son diseño de EDUDRIVE. Un escenario descrito es un requisito curricular para SIMUDRIVE, no una afirmación de que ya existe en el producto.

Se mantienen nombres de componentes provenientes de la conversación de referencia. Sus contratos se definen aquí como arquitectura objetivo; no se ha auditado ni se modifica su implementación actual.

# Alcance y adaptación a Costa Rica

THINK! es una referencia educativa oficial del Department for Transport. Sus mapas organizan seis unidades principales para cada grupo 3–6, 7–12 y 13–16 [R01–R03]. Estos grupos de recursos no equivalen exactamente a los Key Stages del currículo nacional inglés. La biblioteca puede contener recursos adicionales; el comparativo de este EDF cubre las 18 unidades de los mapas.

EDUDRIVE conserva la progresión de hábitos acompañados, autonomía gradual y decisiones responsables. Añade una etapa 17+, once dominios transversales, evidencias por competencia, evaluación de transferencia y seguimiento durante la vida. La filosofía 3–80 incluye a personas mayores de 80: expresa continuidad, no una edad máxima de acceso.

## Contextos que deben aparecer en los recursos

Incluir barrios y centros urbanos, rutas rurales, escuelas, garajes, parqueos, transporte público y escolar, motocicletas, ciclistas y peatones con diversas necesidades. Variar lluvia, iluminación, superficie, obras, pendientes y visibilidad. La falta de infraestructura segura debe permitir esperar, cambiar de ruta o pedir apoyo; nunca debe normalizar una exposición peligrosa.

Localizar vocabulario y señalización: acera, calzada, espaldón, ALTO, ceda, paradas y demarcaciones locales. No importar la circulación británica por la izquierda, su Highway Code, sus edades legales o sus distancias reglamentarias. En cruces se comprueban todas las aproximaciones, giros y sentidos, sin una secuencia lateral rígida que ignore vías de un solo sentido.

## Formación y autorización legal

17+ es una etapa pedagógica. No habilita para conducir. Los artículos 83–85 de la Ley 9078 distinguen requisitos de permiso/licencia y la excepción A1 para personas mayores de dieciséis años bajo condiciones [CR02]. La verificación del requisito vigente se realiza antes de cualquier práctica vial. Un participante de DECIDO elegible para A1 puede cursar contenidos de moto de E4 sin cambiar automáticamente su etapa de desarrollo.

La DGEV anunció pruebas teóricas diferenciadas por clase A y B desde el 2 de marzo de 2026 [CR01]. Por eso la arquitectura separa automóvil y motocicleta. No declara una correspondencia completa con los manuales de 2026 ni reproduce sus bancos oficiales: la producción de lecciones debe mapear cada tema a la edición oficial aplicable.

Los programas de COSEVI contemplan trabajo con centros educativos, municipios y empresas [CR03]. EDUDRIVE incorpora estos contextos para actividades comunitarias; no atribuye a esas instituciones respaldo o participación no documentados.

# Etapas y personalización del aprendizaje

| Etapa | Diferenciación interna | Condición de progresión |
| --- | --- | --- |
| E1 DESCUBRO 3–6 | 3–4: reconocer, imitar y comunicar con gesto o imagen. 5–6: explicar una elección sencilla y repetir en entorno nuevo. | Acompañamiento adulto permanente en entorno vial; desempeño sin pistas sobre la respuesta. |
| E2 COMPRENDO 7–12 | 7–9: comparar peligros concretos y ensayar con apoyo. 10–12: planificar rutas y contingencias. | Autonomía real decidida con cuidadores según entorno y desempeño; nunca otorgada por edad o insignia. |
| E3 DECIDO 13–16 | 13–14: anticipar consecuencias y ensayar asertividad. 15–16: integrar presión social, evidencia y rol de futuro conductor. | Transferencia a casos nuevos; simulación motorizada no equivale a permiso. |
| E4 CONDUZCO 17+ | Ingreso, principiante, experiencia, retorno y actualización según necesidad. | Práctica motorizada condicionada por requisitos legales y supervisión aplicables. |

## Trayectorias durante la vida

De 17 a 24 años se puede priorizar inicio, presión social y adquisición de experiencia. De 25 a 59, exposición laboral, transporte de familia, cambios de vehículo y hábitos. De 60 a 80 y más, accesibilidad, cambios funcionales, actualización y alternativas de movilidad. Estas franjas orientan ejemplos; no presumen deterioro, capacidad ni un nivel por edad.

Una persona adulta puede ingresar por diagnóstico y demostrar prerrequisitos equivalentes sin completar cursos infantiles. Quien no conduce conserva rutas de peatón, ciclista, pasajero y movilidad permanente. Los desempeños no aplicables al rol se registran como no aplicables, nunca como aprobados ni como fracasos.

## Accesibilidad y protección

Permitir voz, texto, pictogramas, lectura asistida, lengua de señas y controles adaptados. Distinguir apoyo de acceso de pista de respuesta. Si una tarea depende de audición o motricidad, ofrecer una demostración equivalente del objetivo cuando sea válido y registrar alcance; un recurso inaccesible genera evidencia insuficiente, no incompetencia.

No usar exposición real al peligro para evaluar. Las actividades infantiles utilizan circuitos, maquetas y observación desde protección. El uso de mapas reales evita domicilios y rutas identificables en publicaciones. Escenarios sin imágenes gráficas, sin competencia por velocidad y con opción de pausa.

# Progresión vertical de los once dominios

Cada familia conserva su código entre etapas. E1–E4 expresan complejidad y contexto de desarrollo; no son notas ni estrellas acumuladas. Las filas siguientes se concretan en las 44 fichas de progresión.

| Dominio | E1 | E2 | E3 | E4 |
| --- | --- | --- | --- | --- |
| PED | Cruza con adulto | Selecciona cruce y respaldo | Resuelve riesgos dinámicos | Se protege y protege cruces al conducir |
| CIC | Controla en patio | Revisa y maniobra en circuito | Planea y cancela conflictos | Circula y protege ciclistas desde otros roles |
| PAS | Pide y conserva protección | Planea abordaje y contingencia | Rechaza viaje inseguro | Verifica ocupantes y transporte |
| SEN | Relaciona señal y acción | Explica función y contexto | Resuelve prioridad e incertidumbre | Aplica y consulta regla vigente |
| RSK | Detecta y avisa | Anticipa un conflicto | Prioriza riesgos simultáneos | Actúa antes del conflicto |
| DEC | Pausa y pide ayuda | Compara rutas y rechaza atajo | Decide bajo presión | Cancela y revisa decisiones complejas |
| PRE | Mejora visibilidad acompañado | Explica distracción y margen | Integra fatiga, velocidad y sustancias | Inspecciona y decide aptitud del viaje |
| CON | Distingue rol autorizado | Entiende límites del vehículo | Prepara responsabilidad futura | Forma y demuestra por tipo de vehículo |
| COV | Espera y deja espacio | Propone mejora escolar | Interviene y evalúa conducta | Desescala y protege diversidad |
| EME | Se resguarda y avisa | Comunica lugar e incidente | Activa ayuda sin añadir riesgo | Gestiona protección y riesgos secundarios |
| SOS | Elige viaje acompañado | Compara modos y ruta | Evalúa un cambio viable | Actualiza movilidad según necesidad |

El dominio previo apoya el siguiente, pero no lo concede. Por ejemplo, cruzar con adulto no demuestra planificación autónoma; anticipar un peligro en video no demuestra control de frenos; conducir automóvil no demuestra equilibrio y frenado de motocicleta.

La edad cambia la presentación, el contexto esperado y las salvaguardas. El nivel de dominio cambia únicamente mediante evidencia. Las competencias ya demostradas se conservan en el historial y se amplían o revalidan con nuevas condiciones.

# Prerrequisitos y reglas de avance

## Identidad estable

Formato de familia: EDU-PED-001. Desempeño de etapa: EDU-PED-001.E2. Subcompetencias observables: EDU-PED-001.E2.S1, S2 y S3. La versión del criterio se guarda aparte, por ejemplo 1.0.0. Cambiar de versión no crea un logro nuevo por sí mismo. Los códigos retirados no se reutilizan.

| Destino | Prerrequisitos curriculares |
| --- | --- |
| E2, E3 o E4 de una familia | Desempeño anterior demostrado o diagnóstico equivalente en las subcompetencias necesarias. La persona que ingresa tardíamente puede acreditarlos por evaluación. |
| CIC E2 y E3 en práctica | Control y frenado previos, PRE correspondiente y preparación del entorno; detener práctica si faltan condiciones. |
| CON-002 E4 automóvil | CON-001, SEN, RSK, DEC y PRE de E4 al menos demostrados en simulación/teoría para la preparación; habilitación legal separada para práctica vial. |
| CON-003 E4 motocicleta | Mismos prerrequisitos comunes y equipo apropiado. CON-002 no se considera equivalente a control de moto. |
| CON-004 y CON-005 E4 | Control del vehículo en CON-002 o CON-003 según ruta, más SEN y RSK E4. Evaluar auto y moto por separado. |
| CON-006 E4 preparación teórica | Sin barrera de licencia para estudiar. Diagnosticar SEN, RSK, DEC y PRE; remediar brechas antes de un ensayo final. |
| PRE-002 E4 actualización | Diagnóstico inicial de experiencia, necesidades y contexto; no obliga a repetir toda la etapa. |

## Algoritmo pedagógico de avance

1. Identificar edad, rol, contexto y apoyos. 2. Diagnosticar sin asumir dominio por escolaridad o licencia previa. 3. Enseñar las brechas. 4. Practicar con retroalimentación. 5. Evaluar con variante nueva y sin pistas. 6. Revisar evidencia y aplicar criterio. 7. Registrar alcance y programar mantenimiento. Un curso completado solo confirma participación.

La regla de prerrequisitos opera por desempeño y subcompetencia, sin ciclos de dependencia: CON-001 E4 se demuestra con expediente y razonamiento; el control operativo se demuestra después en CON-002/003. Una evidencia integrada puede servir a varias competencias solo si cada una tiene observables puntuados de forma explícita.

# Rúbrica común y errores críticos

Cada ficha contiene tres subcompetencias S1–S3. Se puntúan por separado y se conserva el resultado de cada intento. La rúbrica evalúa la acción y su justificación adecuada a la edad; una respuesta verbal correcta no compensa una acción insegura.

| Valor | Descriptor | Efecto |
| --- | --- | --- |
| 0 | No demuestra, actúa de forma insegura o requiere intervención de seguridad. | No acredita; identificar si hubo error crítico. |
| 1 | Actúa parcialmente o solo después de pistas que indican la decisión. | En desarrollo; práctica focalizada. |
| 2 | Cumple el observable sin pistas, con los apoyos de acceso y acompañamiento esperados para su etapa. | Demostrado en ese contexto si satisface las demás condiciones. |
| 3 | Transfiere a una variación nueva, explica el riesgo relevante y ajusta su respuesta. | Evidencia de transferencia, no autorización irrestricta. |

## Regla de dominio

Todas las subcompetencias deben alcanzar al menos 2 en las observaciones exigidas por P1–P4, sin errores críticos y con evidencia admisible. La observación nueva debe alcanzar 3 en la subcompetencia que se haya variado. No se promedian S1–S3 para esconder una brecha; tampoco se promedian desempeños entre dominios.

El campo crítico de cada ficha describe la conducta prohibida que bloquea ese intento. Se evalúa la ocurrencia del error, no la presencia de palabras en una explicación. Si una situación real exige intervención, se detiene la actividad. Un error crítico abre remediación y un nuevo conjunto de observaciones seguras; no borra el historial ni establece una etiqueta permanente sobre la persona.

## Errores técnicos y evidencia no interpretable

Falla de controles, latencia, desconexión, idioma no accesible o registro incompleto invalidan el intento como evidencia de dominio. No se cuentan como error vial del participante. El docente/instructor identifica la causa y permite una alternativa equivalente. No usar tiempo de clic como sustituto de percepción si el dispositivo o apoyo altera esa medida.

Los umbrales y protocolos de este EDF son decisiones iniciales de diseño; no se presentan como estándares de THINK!, COSEVI ni como instrumentos psicométricos validados. Su ajuste requiere pilotaje documentado y una nueva versión de criterio.

# Protocolos de evidencia y mantenimiento

| Protocolo | Mínimo para demostrar | Transferencia y alcance |
| --- | --- | --- |
| P1 E1 | Dos observaciones logradas en días distintos y dos contextos protegidos. Docente valida al menos una; reporte familiar solo complementa. | Una variante nueva con explicación oral/gestual. Acompañamiento adulto forma parte del desempeño esperado. |
| P2 E2 | Dos observaciones logradas en días distintos, dos contextos y una justificación. Habilidades prácticas requieren circuito u observación protegida. | Una variante nueva. El mapa o quiz aislado no acredita autonomía en vía real. |
| P3 E3 | Tres observaciones logradas, al menos dos días y dos contextos; incluir presión o incertidumbre y una variante no ensayada. | Se justifica alternativa y riesgo para terceros. Registrar por separado simulación y práctica. |
| P4 E4 | Tres observaciones logradas, al menos dos días y dos contextos; una variante no ensayada. | Para control y maniobras motorizadas se exigen, dentro de esas evidencias, dos sesiones prácticas supervisadas en vehículo de la ruta. Sin ellas, alcance exclusivamente simulado. |

## Secuencia tras un resultado insuficiente

Precisar S1, S2 o S3 que falta; enseñar esa brecha; practicar con retroalimentación; usar una variante distinta para reevaluar. Repetir exactamente la misma escena con respuesta recordada no aporta evidencia independiente. La nueva acreditación exige completar de nuevo las observaciones mínimas del protocolo sin errores críticos.

## Retención y actualización

Se propone una comprobación de retención entre 14 y 30 días después de demostrar para registrar sostenido. Debe preservar S1–S3 y alcanzar al menos 2 sin error crítico. Esta ventana es una política pedagógica inicial de EDUDRIVE, pendiente de calibración; no es vigencia legal.

Revisión ordinaria propuesta a los 6 meses en E1–E3 y a los 12 meses en E4. Una fecha de revisión vencida cambia el estado a requiere actualización y conserva los logros históricos. Cambios normativos, de vehículo, de entorno, necesidades de apoyo o evidencia contradictoria activan una revisión anticipada de las competencias afectadas.

No disponer de vehículo, simulador o conectividad no equivale a falta de capacidad. El registro señala evidencia pendiente y ofrece práctica o evaluación accesible. La revisión humana determina equivalencias; nunca se rebaja una condición crítica para compensar carencia de recursos.

# Competency Trust Model

El Competency Trust Model administra cuánto respaldo tiene una afirmación curricular. Separa desempeño, calidad de evidencia, alcance y vigencia. No asigna una probabilidad de conducción segura ni un puntaje global de confiabilidad de la persona.

| Dimensión | Estados y regla |
| --- | --- |
| Estado de dominio | No evaluado → en desarrollo → demostrado → sostenido. Requiere actualización cuando falta revisión o cambian condiciones; en revisión ante contradicción o reclamación. |
| Calidad de evidencia | C0 insuficiente: no interpretable o sin procedencia. C1 formativa: práctica con pistas o autoinforme. C2 verificada: observación evaluativa trazable con protocolo completo. C3 corroborada: C2 más verificación independiente de evaluador o contexto práctico pertinente. |
| Modalidad | Declarada, juego, simulación, circuito o práctica vial supervisada. Se conserva cada modalidad; una no se convierte automáticamente en otra. |
| Alcance | Etapa, rol, vehículo, condiciones, apoyos y restricciones observadas. Es obligatorio mostrar límites junto al logro. |
| Vigencia | Fecha de demostración, revisión prevista, versión normativa/criterio y eventos que disparan actualización. |

## Reglas de decisión auditables

Demostrado exige P1–P4, S1–S3 con umbral cumplido, ninguna condición crítica en las evidencias que acreditan, calidad mínima C2 y procedencia válida. Sostenido añade retención válida. C3 mejora corroboración pero no reemplaza retención ni amplía modalidades. Para desempeño práctico motorizado se exige validación del instructor y vehículo correspondiente.

Si faltan evidencias, CTM conserva en desarrollo y explica el requisito faltante. Si aparece evidencia reciente contradictoria, marca en revisión el alcance afectado, conserva lo previo y deriva a evaluador. Un duplicado no cuenta dos veces. Un juego con pistas nunca se reclasifica como evaluación independiente.

Ante error crítico: registrar evento, remediar, obtener nuevo conjunto de evidencia y revisar. Ante falla técnica: invalidar evidencia, sin inferencia de incompetencia. Ante desacuerdo humano: conservar ambas valoraciones y resolver con segunda revisión, razón y responsable.

## Calibración antes de decisiones de alto impacto

Pilotar casos de acuerdo y desacuerdo entre evaluadores, accesibilidad, tasas de evidencia insuficiente, transferencia y retención. Fijar criterios de aceptación y muestra antes del piloto. Cualquier uso externo para selección, seguros o habilitación requiere una evaluación adicional de validez y gobernanza; esta arquitectura solo acredita aprendizajes dentro de su alcance.

# Learning OS y Decision Engine SIMUDRIVE

Flujo curricular: diagnóstico → selección de brecha → experiencia de aprendizaje → práctica → evaluación → evidencia → revisión CTM → registro en Pasaporte Vial → retención y siguiente experiencia. Decision Engine interviene durante el escenario y produce evidencia; CTM decide qué afirmación puede sostenerse con el conjunto de evidencias.

| Componente | Recibe | Produce y límite |
| --- | --- | --- |
| Learning OS | Perfil de edad y rol, apoyos, prerrequisitos, brechas y versión curricular. | Ruta de aprendizaje, propósito, recursos y evaluación pendiente. No concede dominio por tiempo, XP o finalización. |
| SIMUDRIVE | Escenario versionado, condiciones, controles accesibles y objetivo. | Experiencia, estados y acciones observables. La física y representación limitan qué se puede afirmar. |
| Decision Engine | Estado de escena, acciones y reglas de evaluación de la versión fijada. | Eventos, errores, oportunidades de decisión, resultado por observable y explicación. No diagnostica rasgos ni concede licencia. |
| Competency Trust Model | Evidencias admisibles, rúbrica, protocolo y revisión humana. | Estado, calidad, alcance, razones y próxima revisión. No usa un promedio para anular errores críticos. |
| Pasaporte Vial | Resolución CTM y referencias a evidencia. | Registro comprensible y verificable de lo demostrado, condiciones y límites. |

## Contrato de escenario

Todo escenario declara identificador y versión, competencia y S1–S3 objetivo, contexto costarricense, rol/vehículo, condiciones iniciales, variantes, apoyos, acciones posibles, señales observables, alternativas seguras, error crítico, rúbrica, versión normativa y limitaciones. Esperar, cancelar o pedir ayuda deben estar disponibles cuando sean respuestas seguras.

Separar modo práctica con pistas de modo evaluación sin pistas. Fijar escenario y criterio al inicio del intento; un cambio posterior no puede modificar su resultado silenciosamente. Registrar semilla o variante para explicar diferencias entre intentos. La adaptación cambia dificultad entre intentos sin ocultar condiciones evaluadas.

No recompensar rapidez, colisiones evitadas por azar o decisiones riesgosas que casualmente terminan bien. Medir la acción preventiva dentro de una oportunidad interpretable y la justificación. No afirmar seguimiento ocular cuando solo se dispone de clics, ni control de motocicleta cuando se dispone solo de teclado.

# Pasaporte Vial y contrato de evidencia

| Registro | Campos mínimos obligatorios |
| --- | --- |
| Identidad curricular | Persona con identificador interno, familia, etapa, S1–S3 y versión curricular/criterio. Edad se guarda en perfil protegido, no como calificación. |
| Evidencia | ID único, fecha, origen, evaluador y rol, actividad/escenario y versión, variante, modalidad, contexto, vehículo, apoyos y presencia de pistas. |
| Resultado | Puntuación por subcompetencia, acciones observadas, error crítico, validez técnica, explicación y referencias a registros necesarios. |
| Decisión CTM | Estado anterior/nuevo, calidad C0–C3, protocolo, razón, alcance, restricciones, responsable y fecha de revisión. |
| Publicación | Resumen legible, emisor, fecha y mecanismo de verificación de versión. No mostrar domicilio, trayectos precisos o telemetría sensible. |

## Ejemplo completo de interpretación

Persona de 10 años. EDU-PED-001.E2, criterio 1.0.0. Día 1: circuito de cruce, S1=2, S2=2, S3=2, sin error crítico. Día 3: circuito diferente con bus que oculta carril, S1=2, S2=3, S3=3, sin pistas ni error crítico. Docente verifica P2; CTM registra demostrado, C2, alcance circuito protegido. No acredita desplazamiento sin adulto en vía pública. Revisión de retención prevista entre días 17 y 33 respecto del inicio de esta secuencia.

Si el día 24 se mantienen S1–S3 ≥2 en una nueva comprobación, cambia a sostenido. Si solo vio videos o ganó un juego, permanece en desarrollo con evidencia C1. Si una práctica posterior contradice el logro, el alcance afectado queda en revisión sin borrar los registros anteriores.

## Qué verá la persona

“Selecciono un cruce y cambio de plan si no puedo comprobar el tránsito. Demostrado en circuito, con acompañamiento previsto. Evidencia verificada por docente. Próxima revisión: fecha registrada”. Las estrellas, insignias o colores pueden resumir, pero siempre deben desplegar estado, alcance y fecha; no se suman para producir un supuesto nivel universal de seguridad.

Las insignias de etapa requieren todos los desempeños aplicables del perfil en demostrado o sostenido y sin revisiones pendientes. Si una competencia práctica no aplica o no tiene evidencia, la insignia declara expresamente el perfil parcial; no debe denominarse conductor competente. Participación en un curso queda en un historial separado del dominio.

# Perfiles de salida e integración de experiencias

| Perfil | Requisito curricular y alcance |
| --- | --- |
| DESCUBRO acompañado | Once desempeños E1 según apoyos y medios accesibles. CIC puede registrar solo conocimientos si no se observó control; la insignia debe explicitar esa limitación. |
| COMPRENDO con autonomía contextual | Once desempeños E2 aplicables; salida integradora de mapa, cruce y contingencia. La familia decide permisos de desplazamiento por contexto. |
| DECIDO responsable | Once desempeños E3 aplicables; reto que integre percepción, presión social, comunicación y movilidad sostenible. CON conserva alcance preparatorio. |
| CONDUZCO automóvil | Núcleo E4 aplicable, CON-002, CON-004, CON-005 y CON-006 en automóvil; PRE-002 para continuidad. Control y maniobras exigen evidencia práctica. |
| CONDUZCO motocicleta | Núcleo E4 aplicable, CON-003, CON-004, CON-005 y CON-006 en moto; PRE-002 para continuidad. Evidencia de auto no sustituye moto. |
| Movilidad permanente sin conducción | PED, PAS, SEN, RSK, DEC, PRE, COV, EME y SOS en E4; CIC cuando corresponda y PRE-002. CON se puede estudiar sin acreditar operación motorizada. |

## Evaluación integradora

Un reto integrador puede reunir varias fichas, pero el evaluador conserva S1–S3 de cada código. Ejemplo E2: mapa de ruta + escena de cruce + bus perdido. Se pueden observar PED, PAS, DEC y SOS; cada una debe cumplir P2 con evidencia adicional cuando falte. El éxito global del reto no completa automáticamente todas las competencias.

## Duración y organización

Organizar lecciones breves, práctica y reflexión según edad y contexto del centro. No fijar horas como equivalentes de dominio. E1 prioriza juego corporal/representación y participación adulta; E2 problemas concretos y rutas; E3 dilemas y evidencias; E4 práctica por vehículo y transferencia. Todas las etapas deben permitir repetición espaciada y recuperación de brechas.

Los juegos de la matriz son propuestas originales de actividad y pueden implementarse con tarjetas, tablero o pantalla. SIMUDRIVE no es prerrequisito de acceso a la enseñanza; una alternativa equivalente debe conservar los observables y registrar su modalidad.

# Gobernanza y criterios de cierre curricular

| Responsabilidad | Decisión bajo su custodia |
| --- | --- |
| Dirección curricular EDUDRIVE | Publicar versión, resolver alcance y mantener códigos, perfiles y criterios. Rol institucional a designar. |
| Especialista vial de Costa Rica | Verificar reglas, fuentes, vigencia y correspondencia con materiales oficiales antes de publicar contenido normativo. |
| Docente o instructor | Aplicar rúbrica, documentar apoyos, verificar observaciones y derivar casos inseguros o no interpretables. |
| Responsable de evaluación | Pilotar criterios, revisar desacuerdos y auditar evidencia y equidad de instrumentos. |
| Responsables de producto y datos | Implementar contratos aprobados, control de acceso y trazabilidad sin alterar la política curricular. |

## Versionado y revisión

Cambio mayor: modificar sentido de competencia, perfil de salida o regla de acreditación. Cambio menor: añadir desempeños o variantes compatibles. Corrección: errata sin alterar decisión de dominio. Toda publicación registra motivo, códigos afectados, fuentes y fecha efectiva. Un cambio de criterio exige evaluar impacto sobre logros previos; no convertirlos retroactivamente sin conservar la versión original.

Antes de publicar lecciones legales, el registro normativo debe contener regla, fuente primaria, artículo/sección, jurisdicción, fecha de consulta, vigencia comprobada, responsable y competencias afectadas. Requisitos administrativos, multas y plazos no deben fijarse en juegos sin un mecanismo de actualización.

## Privacidad y revisión humana

Minimizar datos, restringir acceso por rol y fijar conservación antes del despliegue. No exigir video ni biometría para toda evidencia: una rúbrica trazable puede bastar. Menores y cuidadores deben conocer qué se registra. Permitir corrección, impugnación y revisión humana; no publicar ranking de riesgo personal.

## Cierre de esta arquitectura

Quedan definidos etapas, dominios, códigos, 50 desempeños, progresión, evaluación, contratos de integración y comparación con las 18 unidades. Para adopción institucional corresponde registrar responsable y fecha de aprobación, revisar localización normativa y pilotar instrumentos. Esas validaciones no se sustituyen con la redacción de este documento.

La siguiente fase podrá producir guiones de lección, bancos de escenarios y rúbricas operativas con estos códigos. No se autoriza en este EDF una migración de datos, un cambio en edudrive-api ni una emisión de credenciales oficiales.

# Comparación THINK! 3–6

Referencia comparativa para 3–6 años. Los títulos identifican unidades oficiales; las adaptaciones y aportes son decisiones curriculares EDUDRIVE. No se establece correspondencia uno a uno con cursos ni se reproducen materiales británicos.

## T01  Stepping stones to road safety

Concepto adoptado: Reconocimiento del entorno y cruce acompañado.

Adaptación a Costa Rica: Acera, calzada y entradas de garaje; circulación local por la derecha y comprobación de todas las aproximaciones.

Aporte EDUDRIVE y trazabilidad: Rutina demostrada; PED, SEN y DEC en E1.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-1-stepping-stones-to-road-safety/)

## T02  Be bright, be seen

Concepto adoptado: Visibilidad y protección al desplazarse.

Adaptación a Costa Rica: Lluvia, atardecer tropical, iluminación irregular y trayectos rurales.

Aporte EDUDRIVE y trazabilidad: Evaluar visibilidad residual; PRE y CIC en E1.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-2-be-bright-be-seen/)

## T03  Safety first

Concepto adoptado: Protección en viajes y bicicleta.

Adaptación a Costa Rica: Sistemas de retención infantil y transporte escolar conforme a normativa costarricense.

Aporte EDUDRIVE y trazabilidad: Diferenciar responsabilidad adulta y conducta infantil; PAS y CIC en E1.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-3-safety-first/)

# Comparación THINK! 3–6

Referencia comparativa para 3–6 años. Los títulos identifican unidades oficiales; las adaptaciones y aportes son decisiones curriculares EDUDRIVE. No se establece correspondencia uno a uno con cursos ni se reproducen materiales británicos.

## T04  Road rangers

Concepto adoptado: Aplicación práctica de hábitos viales.

Adaptación a Costa Rica: Cruces con garajes, parqueos y obstáculos visuales del entorno próximo.

Aporte EDUDRIVE y trazabilidad: Transferencia y error crítico de invasión de calzada; PED, SEN y RSK en E1.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-4-road-rangers/)

## T05  Roads away from home

Concepto adoptado: Transferir hábitos a lugares desconocidos.

Adaptación a Costa Rica: Viajes interurbanos, paradas de bus, zonas costeras y visitas familiares.

Aporte EDUDRIVE y trazabilidad: Variantes nuevas y registro de contexto, no repetición memorizada; PED y PAS en E1.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-5-roads-away-from-home/)

## T06  Road safety warriors

Concepto adoptado: Consolidación mediante participación y modelos adultos.

Adaptación a Costa Rica: Comunidad educativa y cuidadores como acompañantes; diversidad de movilidad.

Aporte EDUDRIVE y trazabilidad: Insignia sustentada en evidencias, sin exigir alfabetización; DEC y COV en E1.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-6-road-safety-warriors/)

# Comparación THINK! 7–12

Referencia comparativa para 7–12 años. Los títulos identifican unidades oficiales; las adaptaciones y aportes son decisiones curriculares EDUDRIVE. No se establece correspondencia uno a uno con cursos ni se reproducen materiales británicos.

## T07  Do you Stop, Look, Listen, Think?

Concepto adoptado: Cruce razonado y atención a distracciones.

Adaptación a Costa Rica: Señalización local, giros y múltiples sentidos; escuchar con apoyos cuando sea necesario.

Aporte EDUDRIVE y trazabilidad: Decisiones trazables y equivalentes accesibles; PED, SEN y DEC en E2.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-1-do-you-stop-look-listen-think/)

## T08  Take the lead

Concepto adoptado: Preparación gradual para viajar sin adulto.

Adaptación a Costa Rica: Autorización familiar según ruta, madurez e infraestructura; no edad universal de autonomía.

Aporte EDUDRIVE y trazabilidad: Diagnóstico de preparación con límites explícitos; PED, CIC, PAS y DEC en E2.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-2-take-the-lead/)

## T09  Map your journey

Concepto adoptado: Planificación de un trayecto más seguro.

Adaptación a Costa Rica: Mapas locales, bus, lluvia, tramos sin acera y accesibilidad.

Aporte EDUDRIVE y trazabilidad: Ruta ficticia o anonimizada, alternativa y evidencia de razonamiento; PAS, DEC y SOS en E2.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-3-map-your-journey/)

# Comparación THINK! 7–12

Referencia comparativa para 7–12 años. Los títulos identifican unidades oficiales; las adaptaciones y aportes son decisiones curriculares EDUDRIVE. No se establece correspondencia uno a uno con cursos ni se reproducen materiales británicos.

## T10  Road ready?

Concepto adoptado: Riesgos locales y viaje independiente autorizado.

Adaptación a Costa Rica: Datos oficiales costarricenses con fecha y ámbito; observación desde recinto protegido.

Aporte EDUDRIVE y trazabilidad: No inferir riesgo individual a partir de un mapa; RSK, PED y DEC en E2.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-4-road-ready/)

## T11  Campaign spotlight

Concepto adoptado: Comunicar seguridad entre pares.

Adaptación a Costa Rica: Problema concreto del centro educativo y coordinación con adultos o municipalidad.

Aporte EDUDRIVE y trazabilidad: Indicador de conducta y accesibilidad; COV en E2.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-5-campaign-spotlight/)

## T12  The science of stopping

Concepto adoptado: Efectos de distracción sobre reacción y detención.

Adaptación a Costa Rica: Ejemplos de lluvia y superficie; unidades métricas y separación conceptual de tiempo y distancia.

Aporte EDUDRIVE y trazabilidad: Experimento no certifica aptitud de conducción; PRE, RSK y CON en E2.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-6-science-of-stopping/)

# Comparación THINK! 13–16

Referencia comparativa para 13–16 años. Los títulos identifican unidades oficiales; las adaptaciones y aportes son decisiones curriculares EDUDRIVE. No se establece correspondencia uno a uno con cursos ni se reproducen materiales británicos.

## T13  Speak up

Concepto adoptado: Asertividad frente a una situación peligrosa.

Adaptación a Costa Rica: Alternativas de retorno, personas de apoyo y transporte realmente disponible.

Aporte EDUDRIVE y trazabilidad: Diálogos ramificados y decisiones bajo presión; PAS y DEC en E3.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-1-speak-up/)

## T14  THINK! Map investigation

Concepto adoptado: Investigación del entorno y planificación.

Adaptación a Costa Rica: Fuentes COSEVI y levantamiento protegido; no trasladar mapas ni estadísticas británicas.

Aporte EDUDRIVE y trazabilidad: Trazabilidad de datos, incertidumbre y propuesta evaluable; PED, RSK, SEN y SOS en E3.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-2-think-map-investigation-pack/)

## T15  Emergency stop

Concepto adoptado: Distracción, reacción y riesgos del futuro conductor.

Adaptación a Costa Rica: Preparación diferenciada auto/moto, condiciones locales y elegibilidad legal separada.

Aporte EDUDRIVE y trazabilidad: Evaluación anticipatoria y puente a conducción; RSK, PRE y CON en E3. No es una unidad de primeros auxilios.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-3-emergency-stop/)

# Comparación THINK! 13–16

Referencia comparativa para 13–16 años. Los títulos identifican unidades oficiales; las adaptaciones y aportes son decisiones curriculares EDUDRIVE. No se establece correspondencia uno a uno con cursos ni se reproducen materiales británicos.

## T16  Campaign HQ

Concepto adoptado: Consecuencias sobre personas y cambio de conducta.

Adaptación a Costa Rica: Intervenciones escolares o comunitarias con trato respetuoso a víctimas.

Aporte EDUDRIVE y trazabilidad: Medición de cambio, sin atribuir reducción de siniestros sin diseño suficiente; COV y CON en E3.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-4-campaign-hq/)

## T17  Dangerous habits

Concepto adoptado: Reflexión sobre hábitos como peatón y pasajero.

Adaptación a Costa Rica: Celular, prisa, presión social y condiciones habituales de desplazamiento local.

Aporte EDUDRIVE y trazabilidad: Reevaluación espaciada y revisión de confianza; PED, DEC y PRE en E3.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-5-dangerous-habits/)

## T18  Small changes

Concepto adoptado: Cambios seguros hacia movilidad sostenible.

Adaptación a Costa Rica: Disponibilidad de bus, infraestructura, lluvia y accesibilidad económica y funcional.

Aporte EDUDRIVE y trazabilidad: Seguimiento voluntario y alternativa segura; CIC y SOS en E3.

[Fuente oficial THINK!](https://www.think.gov.uk/resource/lesson-6-small-changes/)

# Matriz curricular detallada

## EDU-PED-001.E1 Desplazarse y cruzar con seguridad

| Campo | Definición curricular |
| --- | --- |
| Etapa | DESCUBRO |
| Edad | 3–6 |
| Dominio | Peatón |
| Código de competencia | EDU-PED-001.E1 |
| Competencia | Desplazarse y cruzar con seguridad |
| Subcompetencia | S1 Distinguir acera y calzada; S2 Detenerse y observar; S3 Cruzar con adulto |
| Objetivo | Demostrar la rutina de cruce acompañado en dos entornos protegidos. |
| Contenido | Acera, borde, entrada vehicular; detenerse, mirar, escuchar cuando sea posible, pensar y cruzar solo con condiciones seguras y adulto. |
| Actividad | Recorrer una calle de aula y repetir con una entrada de garaje. |
| Juego | Tito busca el camino: mover la figura solo tras comprobar. |
| Escenario SIMUDRIVE | SIM-PED-001-E1: Calle residencial; pelota al borde. Elegir quedarse con el adulto; la pelota queda para recuperación segura. |
| Evidencia | Lista de cotejo docente en circuito y relato con figuras. |
| Evaluación | Rúbrica S1–S3 y protocolo P1; observación y explicación oral, gestual o pictográfica. |
| Criterio de dominio | Protocolo P1. Ejecuta los tres pasos observables y espera la autorización del adulto. Error crítico que impide acreditar: invadir la calzada o perseguir la pelota. |
| Registro en Pasaporte Vial | EDU-PED-001.E1; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T01, T04.

## EDU-CIC-001.E1 Utilizar la bicicleta con seguridad

| Campo | Definición curricular |
| --- | --- |
| Etapa | DESCUBRO |
| Edad | 3–6 |
| Dominio | Ciclista |
| Código de competencia | EDU-CIC-001.E1 |
| Competencia | Utilizar la bicicleta con seguridad |
| Subcompetencia | S1 Reconocer equipo protector; S2 Frenar a una indicación; S3 Respetar el espacio de otros |
| Objetivo | Controlar bicicleta de equilibrio o triciclo en recinto cerrado con supervisión. |
| Contenido | Casco ajustado por adulto, freno, distancia y zona de práctica. |
| Actividad | Verificar equipo con adulto y recorrer tres estaciones. |
| Juego | Semáforo del patio: avanzar, frenar y esperar sin competir por velocidad. |
| Escenario SIMUDRIVE | SIM-CIC-001-E1: Patio cerrado; peatón cruza el circuito. Frenar y ceder espacio. |
| Evidencia | Observación de ajuste asistido y tres maniobras de parada. |
| Evaluación | Rúbrica S1–S3 y protocolo P1; observación y explicación oral, gestual o pictográfica. |
| Criterio de dominio | Protocolo P1. Identifica el equipo, se detiene controladamente y cede paso. Error crítico que impide acreditar: continuar hacia una persona. |
| Registro en Pasaporte Vial | EDU-CIC-001.E1; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T02, T03.

# Matriz curricular detallada

## EDU-PAS-001.E1 Viajar y transportar con protección

| Campo | Definición curricular |
| --- | --- |
| Etapa | DESCUBRO |
| Edad | 3–6 |
| Dominio | Pasajero y transporte |
| Código de competencia | EDU-PAS-001.E1 |
| Competencia | Viajar y transportar con protección |
| Subcompetencia | S1 Pedir protección antes de salir; S2 Permanecer en posición segura; S3 Subir y bajar acompañado |
| Objetivo | Representar un viaje protegido sin atribuir al niño la instalación del dispositivo. |
| Contenido | Retención infantil elegida e instalada por adulto; cinturón; espera en parada; vehículo detenido. |
| Actividad | Dramatizar salida en auto y abordaje de bus con sillas. |
| Juego | El viaje empieza cuando estamos listos: ordenar tarjetas. |
| Escenario SIMUDRIVE | SIM-PAS-001-E1: Auto aún estacionado; falta asegurar al niño. Pedir ayuda antes de iniciar. |
| Evidencia | Dramatización observada y cotejo familiar complementario. |
| Evaluación | Rúbrica S1–S3 y protocolo P1; observación y explicación oral, gestual o pictográfica. |
| Criterio de dominio | Protocolo P1. Pide ayuda, conserva protección y espera la detención para bajar. Error crítico que impide acreditar: bajar de un vehículo en movimiento. |
| Registro en Pasaporte Vial | EDU-PAS-001.E1; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T03, T05, CR02.

## EDU-SEN-001.E1 Interpretar señales en contexto

| Campo | Definición curricular |
| --- | --- |
| Etapa | DESCUBRO |
| Edad | 3–6 |
| Dominio | Señalización |
| Código de competencia | EDU-SEN-001.E1 |
| Competencia | Interpretar señales en contexto |
| Subcompetencia | S1 Reconocer semáforo peatonal; S2 Identificar paso y señal ALTO; S3 Asociar señal con conducta |
| Objetivo | Relacionar tres señales locales con una acción segura, sin suponer que una señal elimina el riesgo. |
| Contenido | Pictogramas peatonales, ALTO y demarcación; verificación con adulto. |
| Actividad | Clasificar fotografías locales y representar la conducta. |
| Juego | Señal y acción: parejas con explicación. |
| Escenario SIMUDRIVE | SIM-SEN-001-E1: Semáforo permite paso, pero un auto sigue moviéndose. Esperar con el adulto. |
| Evidencia | Parejas justificadas y respuesta en escena nueva. |
| Evaluación | Rúbrica S1–S3 y protocolo P1; observación y explicación oral, gestual o pictográfica. |
| Criterio de dominio | Protocolo P1. Relaciona las tres señales y verifica tráfico antes de actuar. Error crítico que impide acreditar: cruzar por el color sin verificar el entorno. |
| Registro en Pasaporte Vial | EDU-SEN-001.E1; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T01, T04, CR02.

# Matriz curricular detallada

## EDU-RSK-001.E1 Percibir y anticipar riesgos

| Campo | Definición curricular |
| --- | --- |
| Etapa | DESCUBRO |
| Edad | 3–6 |
| Dominio | Percepción del riesgo |
| Código de competencia | EDU-RSK-001.E1 |
| Competencia | Percibir y anticipar riesgos |
| Subcompetencia | S1 Detectar un peligro visible; S2 Reconocer una zona oculta; S3 Alejarse y avisar |
| Objetivo | Señalar peligro y buscar protección ante un vehículo o una salida sin visibilidad. |
| Contenido | Vehículo en retroceso, entradas, pelota y obstáculos visuales. |
| Actividad | Explorar cuatro imágenes y ubicar una zona de espera. |
| Juego | Detectives del barrio: encontrar peligro y refugio. |
| Escenario SIMUDRIVE | SIM-RSK-001-E1: Parqueo; vehículo grande oculta otro en movimiento. Permanecer fuera de la trayectoria. |
| Evidencia | Selección de peligros y acción representada, sin puntuar solo clics. |
| Evaluación | Rúbrica S1–S3 y protocolo P1; observación y explicación oral, gestual o pictográfica. |
| Criterio de dominio | Protocolo P1. Localiza el peligro, reconoce lo que no ve y avisa al adulto. Error crítico que impide acreditar: entrar en una zona de movimiento vehicular. |
| Registro en Pasaporte Vial | EDU-RSK-001.E1; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T04.

## EDU-DEC-001.E1 Elegir y revisar decisiones seguras

| Campo | Definición curricular |
| --- | --- |
| Etapa | DESCUBRO |
| Edad | 3–6 |
| Dominio | Toma de decisiones |
| Código de competencia | EDU-DEC-001.E1 |
| Competencia | Elegir y revisar decisiones seguras |
| Subcompetencia | S1 Hacer una pausa; S2 Elegir una alternativa segura; S3 Solicitar ayuda |
| Objetivo | Elegir esperar o pedir ayuda ante una invitación a actuar sin protección. |
| Contenido | Impulso, espera, alternativas y adulto de confianza. |
| Actividad | Dramatizar una pelota perdida y un amigo que llama desde otra acera. |
| Juego | Pausa de Tito: elegir entre esperar, correr o avisar. |
| Escenario SIMUDRIVE | SIM-DEC-001-E1: Amigo llama al otro lado; seleccionar quedarse y pedir acompañamiento. |
| Evidencia | Elección y explicación por voz, gesto o pictograma. |
| Evaluación | Rúbrica S1–S3 y protocolo P1; observación y explicación oral, gestual o pictográfica. |
| Criterio de dominio | Protocolo P1. Se detiene, elige ayuda y mantiene la elección pese a la invitación. Error crítico que impide acreditar: correr hacia la vía para seguir a otro. |
| Registro en Pasaporte Vial | EDU-DEC-001.E1; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T01, T06.

# Matriz curricular detallada

## EDU-PRE-001.E1 Prevenir la exposición y el daño

| Campo | Definición curricular |
| --- | --- |
| Etapa | DESCUBRO |
| Edad | 3–6 |
| Dominio | Prevención |
| Código de competencia | EDU-PRE-001.E1 |
| Competencia | Prevenir la exposición y el daño |
| Subcompetencia | S1 Reconocer baja visibilidad; S2 Elegir elementos visibles; S3 Mantener proximidad al adulto |
| Objetivo | Mostrar cómo mejorar la visibilidad y el acompañamiento ante lluvia u oscuridad. |
| Contenido | Contraste, reflectantes, paraguas que tapa la vista; ser visible no garantiza ser visto. |
| Actividad | Comparar prendas en maqueta iluminada y sombreada. |
| Juego | Veo y me ven: equipar al personaje y elegir posición. |
| Escenario SIMUDRIVE | SIM-PRE-001-E1: Tarde lluviosa; el paraguas bloquea la vista. Parar y reajustarlo con adulto. |
| Evidencia | Demostración con ropa y explicación de un riesgo residual. |
| Evaluación | Rúbrica S1–S3 y protocolo P1; observación y explicación oral, gestual o pictográfica. |
| Criterio de dominio | Protocolo P1. Elige visibilidad, despeja la vista y permanece acompañado. Error crítico que impide acreditar: continuar sin poder ver el tránsito. |
| Registro en Pasaporte Vial | EDU-PRE-001.E1; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T02.

## EDU-CON-001.E1 Comprender y asumir la responsabilidad de conducir

| Campo | Definición curricular |
| --- | --- |
| Etapa | DESCUBRO |
| Edad | 3–6 |
| Dominio | Conducción |
| Código de competencia | EDU-CON-001.E1 |
| Competencia | Comprender y asumir la responsabilidad de conducir |
| Subcompetencia | S1 Reconocer quién conduce; S2 Respetar los controles; S3 Distinguir juguete y vehículo real |
| Objetivo | Explicar que solo una persona autorizada opera el vehículo real. |
| Contenido | Vehículo estacionado no equivale a juguete; llaves y mandos; responsabilidad adulta. |
| Actividad | Usar ilustraciones y maqueta sin acceso a controles reales. |
| Juego | ¿Quién puede conducir?: clasificar escenas. |
| Escenario SIMUDRIVE | SIM-CON-001-E1: Auto estacionado con llaves; pedir al adulto resguardarlas y mantenerse lejos de mandos. |
| Evidencia | Clasificación y dramatización de petición de ayuda. |
| Evaluación | Rúbrica S1–S3 y protocolo P1; observación y explicación oral, gestual o pictográfica. |
| Criterio de dominio | Protocolo P1. Diferencia juego y conducción y respeta los límites de acceso. Error crítico que impide acreditar: manipular controles de un vehículo real. |
| Registro en Pasaporte Vial | EDU-CON-001.E1; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; no acredita manejo motorizado. |
| Ruta de formación | Común |

Referencias: Diseño EDUDRIVE; puente de responsabilidad.

# Matriz curricular detallada

## EDU-COV-001.E1 Convivir y proteger a otras personas

| Campo | Definición curricular |
| --- | --- |
| Etapa | DESCUBRO |
| Edad | 3–6 |
| Dominio | Convivencia vial |
| Código de competencia | EDU-COV-001.E1 |
| Competencia | Convivir y proteger a otras personas |
| Subcompetencia | S1 Esperar turno; S2 Dejar paso; S3 Pedir permiso para ayudar |
| Objetivo | Compartir un recorrido con personas de distintas necesidades de movilidad. |
| Contenido | Espacio peatonal, cortesía y apoyos; diversidad sin estereotipos. |
| Actividad | Recorrer una maqueta con figuras de peatones y silla de ruedas. |
| Juego | La acera es de todos: despejar el paso. |
| Escenario SIMUDRIVE | SIM-COV-001-E1: Acera estrecha; hay una persona con apoyo de movilidad. Esperar sin empujar. |
| Evidencia | Observación del recorrido cooperativo. |
| Evaluación | Rúbrica S1–S3 y protocolo P1; observación y explicación oral, gestual o pictográfica. |
| Criterio de dominio | Protocolo P1. Respeta turno, conserva espacio y pregunta antes de ayudar. Error crítico que impide acreditar: empujar a alguien hacia la calzada. |
| Registro en Pasaporte Vial | EDU-COV-001.E1; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T06.

## EDU-EME-001.E1 Protegerse y activar ayuda

| Campo | Definición curricular |
| --- | --- |
| Etapa | DESCUBRO |
| Edad | 3–6 |
| Dominio | Emergencias |
| Código de competencia | EDU-EME-001.E1 |
| Competencia | Protegerse y activar ayuda |
| Subcompetencia | S1 Alejarse del peligro; S2 Avisar a adulto seguro; S3 Comunicar dónde ocurre |
| Objetivo | Pedir ayuda desde un lugar protegido ante una situación representada. |
| Contenido | Adulto de confianza, referencias del lugar y emergencias; toda llamada de práctica es ficticia. |
| Actividad | Representar un incidente con muñecos y teléfono desconectado. |
| Juego | Mensaje de ayuda: ordenar lugar, qué pasa y petición. |
| Escenario SIMUDRIVE | SIM-EME-001-E1: Bicicleta caída junto a la vía; quedarse a resguardo y avisar sin entrar al tránsito. |
| Evidencia | Dramatización y mensaje comprensible. |
| Evaluación | Rúbrica S1–S3 y protocolo P1; observación y explicación oral, gestual o pictográfica. |
| Criterio de dominio | Protocolo P1. Se protege, avisa y comunica una referencia reconocible. Error crítico que impide acreditar: entrar al tránsito para intentar rescatar. |
| Registro en Pasaporte Vial | EDU-EME-001.E1; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: Diseño EDUDRIVE.

# Matriz curricular detallada

## EDU-SOS-001.E1 Elegir movilidad segura y sostenible

| Campo | Definición curricular |
| --- | --- |
| Etapa | DESCUBRO |
| Edad | 3–6 |
| Dominio | Movilidad sostenible |
| Código de competencia | EDU-SOS-001.E1 |
| Competencia | Elegir movilidad segura y sostenible |
| Subcompetencia | S1 Reconocer modos de viaje; S2 Elegir acompañamiento; S3 Cuidar el espacio común |
| Objetivo | Comparar caminar, pedalear y viajar en bus según protección y compañía. |
| Contenido | Viajes cortos, entorno saludable, accesibilidad y residuos. |
| Actividad | Elegir cómo llegar a un parque en un mapa ficticio. |
| Juego | Vamos al parque: elegir un viaje seguro en equipo. |
| Escenario SIMUDRIVE | SIM-SOS-001-E1: Dos rutas al parque, una sin acera; elegir la protegida con adulto aunque sea más larga. |
| Evidencia | Mapa con elección y explicación sencilla. |
| Evaluación | Rúbrica S1–S3 y protocolo P1; observación y explicación oral, gestual o pictográfica. |
| Criterio de dominio | Protocolo P1. Reconoce modos y antepone una ruta protegida a la más corta. Error crítico que impide acreditar: elegir un atajo que obliga a exponerse al tránsito. |
| Registro en Pasaporte Vial | EDU-SOS-001.E1; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: Diseño EDUDRIVE.

## EDU-PED-001.E2 Desplazarse y cruzar con seguridad

| Campo | Definición curricular |
| --- | --- |
| Etapa | COMPRENDO |
| Edad | 7–12 |
| Dominio | Peatón |
| Código de competencia | EDU-PED-001.E2 |
| Competencia | Desplazarse y cruzar con seguridad |
| Subcompetencia | S1 Seleccionar lugar de cruce; S2 Comprobar todas las aproximaciones; S3 Replanificar si no es seguro |
| Objetivo | Resolver cruces con obstáculos visuales y explicar cuándo esperar o pedir acompañamiento. |
| Contenido | Intersecciones, doble sentido, giros y autos estacionados; autonomía acordada con cuidadores. |
| Actividad | Auditar un trayecto acompañado sin ensayar cruces peligrosos. |
| Juego | Cruza con razones: justificar la elección de lugar. |
| Escenario SIMUDRIVE | SIM-PED-001-E2: Bus detenido oculta otro carril; buscar un cruce con visibilidad y esperar. |
| Evidencia | Mapa anotado y observación de cruce en circuito. |
| Evaluación | Rúbrica S1–S3 y protocolo P2; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P2. Selecciona visibilidad suficiente, revisa aproximaciones y cambia de plan. Error crítico que impide acreditar: cruzar por delante o detrás del bus sin visibilidad. |
| Registro en Pasaporte Vial | EDU-PED-001.E2; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T07, T08.

# Matriz curricular detallada

## EDU-CIC-001.E2 Utilizar la bicicleta con seguridad

| Campo | Definición curricular |
| --- | --- |
| Etapa | COMPRENDO |
| Edad | 7–12 |
| Dominio | Ciclista |
| Código de competencia | EDU-CIC-001.E2 |
| Competencia | Utilizar la bicicleta con seguridad |
| Subcompetencia | S1 Revisar bicicleta y casco; S2 Controlar trayectoria y frenado; S3 Comunicar una maniobra |
| Objetivo | Demostrar preparación y maniobras básicas en circuito antes de considerar una ruta real. |
| Contenido | Frenos, llantas, ajuste, visibilidad y señal manual compatible con control. |
| Actividad | Revisión guiada y circuito con parada y giro. |
| Juego | Taller ciclista: descubrir fallas y decidir si salir. |
| Escenario SIMUDRIVE | SIM-CIC-001-E2: Freno defectuoso antes del viaje; cancelar y solicitar reparación. |
| Evidencia | Lista de inspección y observación de maniobras. |
| Evaluación | Rúbrica S1–S3 y protocolo P2; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P2. Detecta falla crítica, frena y comunica sin perder control. Error crítico que impide acreditar: salir con freno inoperante. |
| Registro en Pasaporte Vial | EDU-CIC-001.E2; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T08, T09; CR02.

## EDU-PAS-001.E2 Viajar y transportar con protección

| Campo | Definición curricular |
| --- | --- |
| Etapa | COMPRENDO |
| Edad | 7–12 |
| Dominio | Pasajero y transporte |
| Código de competencia | EDU-PAS-001.E2 |
| Competencia | Viajar y transportar con protección |
| Subcompetencia | S1 Planificar abordaje y descenso; S2 Mantener protección; S3 Resolver una parada perdida |
| Objetivo | Organizar un viaje en transporte escolar o público con alternativa de ayuda. |
| Contenido | Parada segura, pertenencias, cinturón cuando corresponde, no distraer; plan familiar. |
| Actividad | Simular bus con cambio de parada y contacto de apoyo. |
| Juego | Mi parada segura: decisiones encadenadas. |
| Escenario SIMUDRIVE | SIM-PAS-001-E2: Se pierde la parada; permanecer a bordo de forma segura y pedir orientación. |
| Evidencia | Plan de viaje y dramatización observada. |
| Evaluación | Rúbrica S1–S3 y protocolo P2; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P2. Espera detención, conserva protección y aplica alternativa de ayuda. Error crítico que impide acreditar: intentar bajar mientras el vehículo avanza. |
| Registro en Pasaporte Vial | EDU-PAS-001.E2; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T08, T09, T10.

# Matriz curricular detallada

## EDU-SEN-001.E2 Interpretar señales en contexto

| Campo | Definición curricular |
| --- | --- |
| Etapa | COMPRENDO |
| Edad | 7–12 |
| Dominio | Señalización |
| Código de competencia | EDU-SEN-001.E2 |
| Competencia | Interpretar señales en contexto |
| Subcompetencia | S1 Clasificar señales por función; S2 Explicar el riesgo que regulan; S3 Actuar según el contexto |
| Objetivo | Interpretar señales y demarcaciones locales en un recorrido peatonal y ciclista. |
| Contenido | Reglamentación, prevención e información; ALTO, ceda, zona escolar y semáforos. |
| Actividad | Relacionar seis señales con problemas concretos de una maqueta. |
| Juego | Señales con propósito: reparar un mapa incoherente. |
| Escenario SIMUDRIVE | SIM-SEN-001-E2: Cruce escolar señalizado; un vehículo no cede. Esperar y reevaluar. |
| Evidencia | Mapa corregido y decisiones justificadas. |
| Evaluación | Rúbrica S1–S3 y protocolo P2; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P2. Explica las seis señales trabajadas y verifica condiciones antes de actuar. Error crítico que impide acreditar: confundir prioridad con protección garantizada. |
| Registro en Pasaporte Vial | EDU-SEN-001.E2; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T07, T08; CR02.

## EDU-RSK-001.E2 Percibir y anticipar riesgos

| Campo | Definición curricular |
| --- | --- |
| Etapa | COMPRENDO |
| Edad | 7–12 |
| Dominio | Percepción del riesgo |
| Código de competencia | EDU-RSK-001.E2 |
| Competencia | Percibir y anticipar riesgos |
| Subcompetencia | S1 Distinguir peligro y exposición; S2 Anticipar una posible trayectoria; S3 Elegir una medida preventiva |
| Objetivo | Anticipar un riesgo antes de que el conflicto sea evidente. |
| Contenido | Oclusión, entradas de garaje, velocidad percibida, distracción y clima. |
| Actividad | Pausar videos antes del conflicto y proponer lo que puede ocurrir. |
| Juego | ¿Qué falta por ver?: revelar escenas por partes. |
| Escenario SIMUDRIVE | SIM-RSK-001-E2: Auto estacionado con persona dentro; prever apertura de puerta al pasar en bicicleta. |
| Evidencia | Predicción registrada antes de revelar desenlace. |
| Evaluación | Rúbrica S1–S3 y protocolo P2; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P2. Anticipa la trayectoria y propone separación o espera en escena nueva. Error crítico que impide acreditar: seguir hacia un conflicto que ya identificó. |
| Registro en Pasaporte Vial | EDU-RSK-001.E2; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T10, T12.

# Matriz curricular detallada

## EDU-DEC-001.E2 Elegir y revisar decisiones seguras

| Campo | Definición curricular |
| --- | --- |
| Etapa | COMPRENDO |
| Edad | 7–12 |
| Dominio | Toma de decisiones |
| Código de competencia | EDU-DEC-001.E2 |
| Competencia | Elegir y revisar decisiones seguras |
| Subcompetencia | S1 Comparar alternativas de ruta; S2 Rechazar presión de pares; S3 Activar un plan de respaldo |
| Objetivo | Elegir una ruta viable y sostener la decisión segura frente a un atajo riesgoso. |
| Contenido | Seguridad, accesibilidad, tiempo, comunicación y autorización familiar. |
| Actividad | Construir un mapa ficticio con dos rutas y una alternativa por lluvia. |
| Juego | La ruta razonada: ganar por justificar, sin premiar rapidez. |
| Escenario SIMUDRIVE | SIM-DEC-001-E2: Amigos proponen cruzar fuera del punto previsto; rechazar y usar ruta acordada. |
| Evidencia | Mapa, justificación y diálogo de rechazo. |
| Evaluación | Rúbrica S1–S3 y protocolo P2; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P2. Compara tres factores, rechaza el atajo y comunica el cambio de plan. Error crítico que impide acreditar: seguir al grupo a una zona sin cruce seguro. |
| Registro en Pasaporte Vial | EDU-DEC-001.E2; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T08, T09, T10.

## EDU-PRE-001.E2 Prevenir la exposición y el daño

| Campo | Definición curricular |
| --- | --- |
| Etapa | COMPRENDO |
| Edad | 7–12 |
| Dominio | Prevención |
| Código de competencia | EDU-PRE-001.E2 |
| Competencia | Prevenir la exposición y el daño |
| Subcompetencia | S1 Detectar distracciones; S2 Explicar reacción y frenado; S3 Preparar el viaje |
| Objetivo | Explicar por qué la distracción y la lluvia reducen el margen de seguridad. |
| Contenido | Distancia de reacción más distancia de frenado; velocidad y adherencia; sin fórmulas de distancia universal. |
| Actividad | Experimento con regla y carrito en aula, sin inferir aptitud de conducción. |
| Juego | Margen seguro: comparar condiciones del mismo trayecto. |
| Escenario SIMUDRIVE | SIM-PRE-001-E2: Lluvia y mensaje en teléfono al acercarse a un cruce; guardar teléfono y esperar. |
| Evidencia | Registro del experimento y decisión contextual. |
| Evaluación | Rúbrica S1–S3 y protocolo P2; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P2. Diferencia reacción y frenado y elimina la distracción antes de moverse. Error crítico que impide acreditar: usar pantalla mientras cruza. |
| Registro en Pasaporte Vial | EDU-PRE-001.E2; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T12.

# Matriz curricular detallada

## EDU-CON-001.E2 Comprender y asumir la responsabilidad de conducir

| Campo | Definición curricular |
| --- | --- |
| Etapa | COMPRENDO |
| Edad | 7–12 |
| Dominio | Conducción |
| Código de competencia | EDU-CON-001.E2 |
| Competencia | Comprender y asumir la responsabilidad de conducir |
| Subcompetencia | S1 Explicar que detenerse toma distancia; S2 Identificar puntos sin visibilidad; S3 Respetar el rol del conductor |
| Objetivo | Comprender límites del conductor desde el rol de peatón o pasajero. |
| Contenido | Campo visual, masa y movimiento; no invadir mandos ni distraer. |
| Actividad | Maqueta de un bus y figuras ocultas, sin acercarse a vehículos operativos. |
| Juego | Desde el asiento: descubrir a quién no se ve. |
| Escenario SIMUDRIVE | SIM-CON-001-E2: Bus inicia un giro; permanecer lejos de su trayectoria y no asumir contacto visual. |
| Evidencia | Maqueta explicada y respuesta a dos perspectivas. |
| Evaluación | Rúbrica S1–S3 y protocolo P2; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P2. Reconoce un punto oculto y mantiene una posición protegida. Error crítico que impide acreditar: acercarse al vehículo para comprobar si lo ven. |
| Registro en Pasaporte Vial | EDU-CON-001.E2; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; no acredita manejo motorizado. |
| Ruta de formación | Común |

Referencias: T12; Diseño EDUDRIVE.

## EDU-COV-001.E2 Convivir y proteger a otras personas

| Campo | Definición curricular |
| --- | --- |
| Etapa | COMPRENDO |
| Edad | 7–12 |
| Dominio | Convivencia vial |
| Código de competencia | EDU-COV-001.E2 |
| Competencia | Convivir y proteger a otras personas |
| Subcompetencia | S1 Reconocer necesidades de otros; S2 Comunicar una conducta segura; S3 Proponer una mejora del entorno |
| Objetivo | Proponer una mejora escolar basada en observación sin exponer a compañeros. |
| Contenido | Respeto, accesibilidad, espacio público y corresponsabilidad. |
| Actividad | Observar desde recinto protegido una entrada escolar y diseñar mensaje. |
| Juego | Consejo de barrio: acordar una solución inclusiva. |
| Escenario SIMUDRIVE | SIM-COV-001-E2: Moto bloquea rampa; proponer intervención de adulto responsable sin confrontación en vía. |
| Evidencia | Campaña con problema, destinatario y conducta verificable. |
| Evaluación | Rúbrica S1–S3 y protocolo P2; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P2. Incluye accesibilidad, comunica con respeto y propone una acción realizable. Error crítico que impide acreditar: confrontar a un conductor en circulación. |
| Registro en Pasaporte Vial | EDU-COV-001.E2; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T11; CR03.

# Matriz curricular detallada

## EDU-EME-001.E2 Protegerse y activar ayuda

| Campo | Definición curricular |
| --- | --- |
| Etapa | COMPRENDO |
| Edad | 7–12 |
| Dominio | Emergencias |
| Código de competencia | EDU-EME-001.E2 |
| Competencia | Protegerse y activar ayuda |
| Subcompetencia | S1 Reconocer cuándo pedir ayuda; S2 Describir lugar e incidente; S3 Seguir instrucciones desde protección |
| Objetivo | Realizar una llamada ficticia de ayuda con ubicación y descripción claras. |
| Contenido | 9-1-1 para emergencias, referencias locales y contacto adulto; no bromas ni rescate en tránsito. |
| Actividad | Simulación con docente como operador y teléfono sin conexión. |
| Juego | Ubica la ayuda: ordenar datos del reporte. |
| Escenario SIMUDRIVE | SIM-EME-001-E2: Incidente cerca de la escuela; informar desde un punto protegido y esperar instrucciones. |
| Evidencia | Audio ficticio o rúbrica de conversación sin grabación. |
| Evaluación | Rúbrica S1–S3 y protocolo P2; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P2. Da lugar y qué ocurre, escucha y permanece a resguardo. Error crítico que impide acreditar: acercarse al tránsito para obtener más detalles. |
| Registro en Pasaporte Vial | EDU-EME-001.E2; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: Diseño EDUDRIVE.

## EDU-SOS-001.E2 Elegir movilidad segura y sostenible

| Campo | Definición curricular |
| --- | --- |
| Etapa | COMPRENDO |
| Edad | 7–12 |
| Dominio | Movilidad sostenible |
| Código de competencia | EDU-SOS-001.E2 |
| Competencia | Elegir movilidad segura y sostenible |
| Subcompetencia | S1 Comparar modos de transporte; S2 Evaluar seguridad y accesibilidad; S3 Diseñar una ruta sostenible |
| Objetivo | Planificar un trayecto de bajo impacto que sea viable para las personas participantes. |
| Contenido | Caminata, bicicleta y bus; distancia, infraestructura, clima y acompañamiento. |
| Actividad | Comparar tres opciones para un viaje escolar ficticio. |
| Juego | Viaje posible: presupuesto de tiempo y protección. |
| Escenario SIMUDRIVE | SIM-SOS-001-E2: Ruta corta sin acera y ruta con bus y cruce protegido; elegir la opción viable. |
| Evidencia | Ficha comparativa con razones y plan alternativo. |
| Evaluación | Rúbrica S1–S3 y protocolo P2; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P2. Valora impacto sin sacrificar seguridad ni excluir apoyos de movilidad. Error crítico que impide acreditar: elegir menor impacto ignorando una exposición crítica. |
| Registro en Pasaporte Vial | EDU-SOS-001.E2; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: Diseño EDUDRIVE.

# Matriz curricular detallada

## EDU-PED-001.E3 Desplazarse y cruzar con seguridad

| Campo | Definición curricular |
| --- | --- |
| Etapa | DECIDO |
| Edad | 13–16 |
| Dominio | Peatón |
| Código de competencia | EDU-PED-001.E3 |
| Competencia | Desplazarse y cruzar con seguridad |
| Subcompetencia | S1 Leer conflictos dinámicos; S2 Rechazar huecos insuficientes; S3 Revisar la ruta en tiempo real |
| Objetivo | Resolver un cruce complejo sin asumir que todos los vehículos cumplirán la señal. |
| Contenido | Varios carriles, giros, motocicletas y visibilidad cambiante. |
| Actividad | Analizar video desde dos perspectivas y defender una decisión. |
| Juego | Ventana segura: esperar también es una respuesta. |
| Escenario SIMUDRIVE | SIM-PED-001-E3: Vehículo cede en un carril y otro sigue; esperar hasta verificar ambos. |
| Evidencia | Secuencia de decisiones y explicación posterior. |
| Evaluación | Rúbrica S1–S3 y protocolo P3; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P3. Comprueba todos los carriles y mantiene margen ante incertidumbre. Error crítico que impide acreditar: entrar al carril que permanece oculto. |
| Registro en Pasaporte Vial | EDU-PED-001.E3; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T14, T17.

## EDU-CIC-001.E3 Utilizar la bicicleta con seguridad

| Campo | Definición curricular |
| --- | --- |
| Etapa | DECIDO |
| Edad | 13–16 |
| Dominio | Ciclista |
| Código de competencia | EDU-CIC-001.E3 |
| Competencia | Utilizar la bicicleta con seguridad |
| Subcompetencia | S1 Seleccionar posición y ruta; S2 Negociar intersecciones; S3 Cancelar una maniobra insegura |
| Objetivo | Planificar ciclismo defensivo con dominio previo de control y frenado. |
| Contenido | Visibilidad, puertas, buses, giro, clima y comunicación; normativa local. |
| Actividad | Circuito y análisis de una ruta sin publicar domicilio. |
| Juego | Ruta ciclista cambiante: reparar el plan ante un obstáculo. |
| Escenario SIMUDRIVE | SIM-CIC-001-E3: Obra obliga a salir del recorrido; detenerse en lugar seguro y elegir alternativa. |
| Evidencia | Observación en circuito y decisiones en escenario nuevo. |
| Evaluación | Rúbrica S1–S3 y protocolo P3; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P3. Se posiciona, comunica y cancela si falta separación o visibilidad. Error crítico que impide acreditar: invadir trayectoria de un vehículo para continuar. |
| Registro en Pasaporte Vial | EDU-CIC-001.E3; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T14, T18; CR02.

# Matriz curricular detallada

## EDU-PAS-001.E3 Viajar y transportar con protección

| Campo | Definición curricular |
| --- | --- |
| Etapa | DECIDO |
| Edad | 13–16 |
| Dominio | Pasajero y transporte |
| Código de competencia | EDU-PAS-001.E3 |
| Competencia | Viajar y transportar con protección |
| Subcompetencia | S1 Detectar conducción insegura; S2 Expresar desacuerdo; S3 Elegir transporte alternativo |
| Objetivo | Intervenir de manera segura cuando un conductor está distraído o ha consumido sustancias. |
| Contenido | Presión social, cinturón, punto seguro para detenerse y adulto de apoyo. |
| Actividad | Ensayar frases de rechazo con alternativas de retorno. |
| Juego | Regreso seguro: conversación ramificada. |
| Escenario SIMUDRIVE | SIM-PAS-001-E3: Conductor ofrece viaje tras beber; rechazar antes de subir y activar apoyo. |
| Evidencia | Diálogo y plan alternativo con recursos disponibles. |
| Evaluación | Rúbrica S1–S3 y protocolo P3; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P3. Expresa límite, evita subir y consigue una alternativa segura. Error crítico que impide acreditar: aceptar viaje con conductor bajo efectos de sustancias. |
| Registro en Pasaporte Vial | EDU-PAS-001.E3; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T13, T15.

## EDU-SEN-001.E3 Interpretar señales en contexto

| Campo | Definición curricular |
| --- | --- |
| Etapa | DECIDO |
| Edad | 13–16 |
| Dominio | Señalización |
| Código de competencia | EDU-SEN-001.E3 |
| Competencia | Interpretar señales en contexto |
| Subcompetencia | S1 Resolver prioridad contextual; S2 Reconocer señalización temporal; S3 Explicar incertidumbre y espera |
| Objetivo | Interpretar prioridades y señales locales en intersecciones y obras desde varios roles. |
| Contenido | Semáforos, demarcación, indicaciones de autoridad y señales temporales. |
| Actividad | Resolver casos con regla local documentada y contrastar razones. |
| Juego | Cruce argumentado: ordenar acciones sin competir por pasar. |
| Escenario SIMUDRIVE | SIM-SEN-001-E3: Obras cambian circulación; reconocer instrucción aplicable y esperar si el paso es incierto. |
| Evidencia | Resolución escrita u oral con referencia de la regla trabajada. |
| Evaluación | Rúbrica S1–S3 y protocolo P3; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P3. Resuelve señales trabajadas y reconoce cuándo necesita aclaración. Error crítico que impide acreditar: continuar ante una instrucción de detenerse. |
| Registro en Pasaporte Vial | EDU-SEN-001.E3; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T14; CR02.

# Matriz curricular detallada

## EDU-RSK-001.E3 Percibir y anticipar riesgos

| Campo | Definición curricular |
| --- | --- |
| Etapa | DECIDO |
| Edad | 13–16 |
| Dominio | Percepción del riesgo |
| Código de competencia | EDU-RSK-001.E3 |
| Competencia | Percibir y anticipar riesgos |
| Subcompetencia | S1 Anticipar varios desenlaces; S2 Priorizar por gravedad y exposición; S3 Ajustar la acción antes del conflicto |
| Objetivo | Anticipar riesgos simultáneos y justificar una respuesta preventiva. |
| Contenido | Oclusión, sobreconfianza, condiciones adversas; incertidumbre y usuarios vulnerables. |
| Actividad | Pausar un video antes del conflicto y ordenar hipótesis. |
| Juego | Tres futuros: elegir qué prevenir primero. |
| Escenario SIMUDRIVE | SIM-RSK-001-E3: Bus, motociclista y peatón convergen; priorizar protección y esperar sin invadir trayectorias. |
| Evidencia | Predicción previa, acción y justificación; no medir solo rapidez del clic. |
| Evaluación | Rúbrica S1–S3 y protocolo P3; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P3. Identifica el riesgo prioritario y actúa antes de entrar en conflicto. Error crítico que impide acreditar: privilegiar avanzar sobre proteger una trayectoria ocupada. |
| Registro en Pasaporte Vial | EDU-RSK-001.E3; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T14, T15.

## EDU-DEC-001.E3 Elegir y revisar decisiones seguras

| Campo | Definición curricular |
| --- | --- |
| Etapa | DECIDO |
| Edad | 13–16 |
| Dominio | Toma de decisiones |
| Código de competencia | EDU-DEC-001.E3 |
| Competencia | Elegir y revisar decisiones seguras |
| Subcompetencia | S1 Reconocer presión social; S2 Elegir una salida segura; S3 Revisar consecuencias y plan |
| Objetivo | Sostener una decisión segura bajo presión de tiempo o grupo. |
| Contenido | Asertividad, alternativas, riesgo para terceros y revisión posterior. |
| Actividad | Debatir un caso con opciones igualmente disponibles y practicar respuesta. |
| Juego | Decido aunque me presionen: diálogo con segunda oportunidad. |
| Escenario SIMUDRIVE | SIM-DEC-001-E3: Llegan tarde; el grupo exige un cruce peligroso. Rechazar y comunicar demora. |
| Evidencia | Decisión, frase de rechazo y plan de recuperación. |
| Evaluación | Rúbrica S1–S3 y protocolo P3; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P3. Mantiene límite seguro y propone alternativa pese a presión renovada. Error crítico que impide acreditar: aceptar un riesgo crítico para evitar desaprobación. |
| Registro en Pasaporte Vial | EDU-DEC-001.E3; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T13, T17.

# Matriz curricular detallada

## EDU-PRE-001.E3 Prevenir la exposición y el daño

| Campo | Definición curricular |
| --- | --- |
| Etapa | DECIDO |
| Edad | 13–16 |
| Dominio | Prevención |
| Código de competencia | EDU-PRE-001.E3 |
| Competencia | Prevenir la exposición y el daño |
| Subcompetencia | S1 Explicar factores humanos; S2 Relacionar velocidad y margen; S3 Preparar medidas preventivas |
| Objetivo | Explicar cómo fatiga, sustancias y distracción alteran decisiones y detención. |
| Contenido | Distancia total = reacción + frenado; diferencia entre tiempo y distancia; no consumo al conducir. |
| Actividad | Comparar escenarios con iguales condiciones salvo velocidad o distracción. |
| Juego | Laboratorio de márgenes: cambiar una variable y predecir. |
| Escenario SIMUDRIVE | SIM-PRE-001-E3: Regreso nocturno con conductor fatigado; detener el plan y organizar relevo o transporte. |
| Evidencia | Predicción física cualitativa y plan preventivo. |
| Evaluación | Rúbrica S1–S3 y protocolo P3; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P3. Explica ambas distancias y elige no iniciar un viaje inseguro. Error crítico que impide acreditar: tratar una bebida estimulante como solución a la fatiga. |
| Registro en Pasaporte Vial | EDU-PRE-001.E3; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T15, T17.

## EDU-CON-001.E3 Comprender y asumir la responsabilidad de conducir

| Campo | Definición curricular |
| --- | --- |
| Etapa | DECIDO |
| Edad | 13–16 |
| Dominio | Conducción |
| Código de competencia | EDU-CON-001.E3 |
| Competencia | Comprender y asumir la responsabilidad de conducir |
| Subcompetencia | S1 Distinguir preparación y habilitación; S2 Reconocer obligaciones del conductor; S3 Explicar una decisión defensiva |
| Objetivo | Construir un plan de preparación por tipo de vehículo sin asumir permiso para conducir. |
| Contenido | Normativa local, usuarios vulnerables, auto y moto; conducción simulada y límites legales. |
| Actividad | Analizar un caso desde conductor, pasajero y peatón. |
| Juego | Preconductor responsable: elegir requisitos y prioridades. |
| Escenario SIMUDRIVE | SIM-CON-001-E3: Intersección con peatón oculto; anticipar reducción y espera desde rol simulado. |
| Evidencia | Plan personal y razonamiento situacional; no acredita control real. |
| Evaluación | Rúbrica S1–S3 y protocolo P3; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P3. Diferencia formación de licencia y protege al usuario vulnerable. Error crítico que impide acreditar: atribuir habilitación vial a una insignia EDUDRIVE. |
| Registro en Pasaporte Vial | EDU-CON-001.E3; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; no acredita manejo motorizado. |
| Ruta de formación | Común |

Referencias: T15, T16; CR01, CR02.

# Matriz curricular detallada

## EDU-COV-001.E3 Convivir y proteger a otras personas

| Campo | Definición curricular |
| --- | --- |
| Etapa | DECIDO |
| Edad | 13–16 |
| Dominio | Convivencia vial |
| Código de competencia | EDU-COV-001.E3 |
| Competencia | Convivir y proteger a otras personas |
| Subcompetencia | S1 Reconocer efectos sobre terceros; S2 Contrastar información; S3 Diseñar intervención evaluable |
| Objetivo | Crear una intervención de convivencia que mida una conducta concreta. |
| Contenido | Sistema Seguro, responsabilidad compartida, trato respetuoso y datos sin culpar a víctimas. |
| Actividad | Observar un entorno protegido y formular una campaña con medición antes/después. |
| Juego | Comité de movilidad: defender soluciones con evidencia. |
| Escenario SIMUDRIVE | SIM-COV-001-E3: Conflicto ciclista-conductor; elegir desescalar y dejar espacio. |
| Evidencia | Propuesta con indicador, muestra y límites de interpretación. |
| Evaluación | Rúbrica S1–S3 y protocolo P3; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P3. Formula un cambio observable, reconoce límites y actúa sin hostilidad. Error crítico que impide acreditar: promover intimidación o castigo en la vía. |
| Registro en Pasaporte Vial | EDU-COV-001.E3; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T16; CR03.

## EDU-EME-001.E3 Protegerse y activar ayuda

| Campo | Definición curricular |
| --- | --- |
| Etapa | DECIDO |
| Edad | 13–16 |
| Dominio | Emergencias |
| Código de competencia | EDU-EME-001.E3 |
| Competencia | Protegerse y activar ayuda |
| Subcompetencia | S1 Evaluar protección propia; S2 Activar ayuda precisa; S3 Respetar límites de intervención |
| Objetivo | Priorizar protección y comunicación ante un siniestro representado. |
| Contenido | 9-1-1, ubicación, riesgos secundarios, privacidad de víctimas e instrucciones del operador. |
| Actividad | Resolver una simulación sin imágenes gráficas ni maniobras clínicas. |
| Juego | Cadena de ayuda: elegir qué hacer y qué evitar. |
| Escenario SIMUDRIVE | SIM-EME-001-E3: Colisión en curva; mantenerse a salvo, reportar ubicación y no entrar a dirigir tránsito. |
| Evidencia | Reporte simulado y lista de acciones evitadas. |
| Evaluación | Rúbrica S1–S3 y protocolo P3; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P3. Activa ayuda y no añade exposición al incidente. Error crítico que impide acreditar: entrar en una escena insegura o manipular víctimas sin indicación competente. |
| Registro en Pasaporte Vial | EDU-EME-001.E3; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: Diseño EDUDRIVE.

# Matriz curricular detallada

## EDU-SOS-001.E3 Elegir movilidad segura y sostenible

| Campo | Definición curricular |
| --- | --- |
| Etapa | DECIDO |
| Edad | 13–16 |
| Dominio | Movilidad sostenible |
| Código de competencia | EDU-SOS-001.E3 |
| Competencia | Elegir movilidad segura y sostenible |
| Subcompetencia | S1 Comparar impacto y accesibilidad; S2 Diseñar viaje multimodal; S3 Revisar hábitos con evidencia |
| Objetivo | Adoptar un cambio de movilidad seguro y evaluar su viabilidad. |
| Contenido | Bus, caminata, bicicleta; costos, tiempo, barreras, clima e impacto ambiental cualitativo. |
| Actividad | Registrar una semana voluntaria con trayectos anonimizados y proponer un cambio. |
| Juego | Pequeño cambio viable: elegir acciones sostenibles realizables. |
| Escenario SIMUDRIVE | SIM-SOS-001-E3: Lluvia impide pedalear con seguridad; elegir combinación bus y caminata protegida. |
| Evidencia | Comparación previa/posterior y reflexión sobre barreras. |
| Evaluación | Rúbrica S1–S3 y protocolo P3; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P3. Justifica cambio y alternativa sin excluir a quienes necesitan apoyos. Error crítico que impide acreditar: mantener un modo inseguro por cumplir una meta ambiental. |
| Registro en Pasaporte Vial | EDU-SOS-001.E3; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: T18.

## EDU-PED-001.E4 Desplazarse y cruzar con seguridad

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Peatón |
| Código de competencia | EDU-PED-001.E4 |
| Competencia | Desplazarse y cruzar con seguridad |
| Subcompetencia | S1 Mantener movilidad peatonal segura; S2 Anticipar presencia de peatones al conducir; S3 Ceder y verificar el cruce |
| Objetivo | Proteger al peatón desde ambos roles, especialmente con visibilidad parcial. |
| Contenido | Zonas escolares, accesibilidad, giros y marcha atrás; deberes locales. |
| Actividad | Inspección de ruta a pie y práctica supervisada según habilitación. |
| Juego | Cambio de perspectiva: resolver la misma escena desde dos roles. |
| Escenario SIMUDRIVE | SIM-PED-001-E4: Cruce escolar con peatón parcialmente oculto; reducir, detenerse si corresponde y verificar. |
| Evidencia | Observación peatonal y desempeño de conducción por modalidad. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Identifica exposición propia y protege el cruce sin presión al peatón. Error crítico que impide acreditar: invadir un cruce ocupado o iniciar marcha sin comprobar. |
| Registro en Pasaporte Vial | EDU-PED-001.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: CR02.

# Matriz curricular detallada

## EDU-CIC-001.E4 Utilizar la bicicleta con seguridad

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Ciclista |
| Código de competencia | EDU-CIC-001.E4 |
| Competencia | Utilizar la bicicleta con seguridad |
| Subcompetencia | S1 Preparar viaje en bicicleta; S2 Resolver conflictos como ciclista; S3 Proteger ciclistas al conducir |
| Objetivo | Gestionar rutas y adelantamientos seguros desde ambos roles. |
| Contenido | Visibilidad, puertas, separación legal aplicable y espera cuando falta espacio. |
| Actividad | Circuito ciclista y análisis supervisado de adelantamiento. |
| Juego | Compartimos carril: seleccionar separación y momento. |
| Escenario SIMUDRIVE | SIM-CIC-001-E4: Ciclista en tramo estrecho con vehículo de frente; esperar sin forzar adelantamiento. |
| Evidencia | Lista de preparación y registro de decisiones por rol. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Mantiene control y espera hasta disponer de espacio seguro y legal. Error crítico que impide acreditar: adelantar sin separación o visibilidad suficientes. |
| Registro en Pasaporte Vial | EDU-CIC-001.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: CR02.

## EDU-PAS-001.E4 Viajar y transportar con protección

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Pasajero y transporte |
| Código de competencia | EDU-PAS-001.E4 |
| Competencia | Viajar y transportar con protección |
| Subcompetencia | S1 Comprobar protección de ocupantes; S2 Gestionar abordaje y carga; S3 Mantener un viaje sin distracción |
| Objetivo | Preparar un transporte seguro en auto y un viaje responsable en bus o moto. |
| Contenido | Cinturones, selección e instalación de SRI, equipaje, puertas y límites de capacidad; manual del fabricante. |
| Actividad | Demostrar inspección previa y resolver una incompatibilidad de SRI. |
| Juego | Antes de arrancar: detectar omisiones de protección. |
| Escenario SIMUDRIVE | SIM-PAS-001-E4: SRI incompatible y presión por salir; detener el inicio y buscar solución adecuada. |
| Evidencia | Cotejo práctico de protección y resolución de caso. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Verifica ocupantes, objetos y dispositivos antes del movimiento. Error crítico que impide acreditar: iniciar marcha sin protección requerida de un ocupante. |
| Registro en Pasaporte Vial | EDU-PAS-001.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: CR02.

# Matriz curricular detallada

## EDU-SEN-001.E4 Interpretar señales en contexto

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Señalización |
| Código de competencia | EDU-SEN-001.E4 |
| Competencia | Interpretar señales en contexto |
| Subcompetencia | S1 Consultar norma vigente; S2 Aplicar prioridad y demarcación; S3 Resolver cambios temporales |
| Objetivo | Aplicar reglas locales al vehículo de la ruta elegida y justificar la decisión. |
| Contenido | Señales verticales/horizontales, autoridad, semáforos y obras; fuente y fecha de vigencia. |
| Actividad | Resolver casos y contrastarlos con fuente normativa controlada. |
| Juego | Regla en acción: explicar la conducta además del símbolo. |
| Escenario SIMUDRIVE | SIM-SEN-001-E4: Intersección con obra y nueva demarcación; actuar conforme a la instrucción aplicable. |
| Evidencia | Casos con referencia y registro de conducta simulada o práctica. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Interpreta reglas de los casos y responde correctamente a detención y prioridad. Error crítico que impide acreditar: desobedecer una señal o indicación de detención. |
| Registro en Pasaporte Vial | EDU-SEN-001.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: CR01, CR02.

## EDU-RSK-001.E4 Percibir y anticipar riesgos

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Percepción del riesgo |
| Código de competencia | EDU-RSK-001.E4 |
| Competencia | Percibir y anticipar riesgos |
| Subcompetencia | S1 Explorar el entorno; S2 Anticipar trayectorias ocultas; S3 Mantener margen de seguridad |
| Objetivo | Detectar riesgos en desarrollo y ajustar velocidad, posición o espera. |
| Contenido | Exploración visual, puntos ciegos, distancia, usuarios vulnerables y incertidumbre. |
| Actividad | Conducción comentada en simulación y práctica supervisada habilitada. |
| Juego | Antes del conflicto: explicar señales tempranas. |
| Escenario SIMUDRIVE | SIM-RSK-001-E4: Vehículo estacionado oculta peatón y moto se aproxima; reducir exposición antes de pasar. |
| Evidencia | Telemetría de acción y explicación; observación práctica separada. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Anticipa antes del conflicto y conserva margen sin exigir una reacción extrema. Error crítico que impide acreditar: mantener trayectoria hacia un peligro detectado. |
| Registro en Pasaporte Vial | EDU-RSK-001.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: CR02; Diseño EDUDRIVE.

# Matriz curricular detallada

## EDU-DEC-001.E4 Elegir y revisar decisiones seguras

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Toma de decisiones |
| Código de competencia | EDU-DEC-001.E4 |
| Competencia | Elegir y revisar decisiones seguras |
| Subcompetencia | S1 Elegir bajo incertidumbre; S2 Cancelar una maniobra insegura; S3 Revisar y corregir el plan |
| Objetivo | Priorizar protección frente a prisa, presión económica o de pasajeros. |
| Contenido | Costo de error, margen, alternativas y no iniciar/no continuar como decisiones válidas. |
| Actividad | Analizar dilemas laborales y realizar revisión posterior sin culpabilización. |
| Juego | La entrega puede esperar: elegir y explicar alternativas. |
| Escenario SIMUDRIVE | SIM-DEC-001-E4: Reparto con plazo imposible bajo lluvia; detenerse en lugar seguro y reprogramar. |
| Evidencia | Árbol de decisiones y conducta en escenario variable. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Cancela a tiempo, explica riesgo y activa una alternativa viable. Error crítico que impide acreditar: mantener maniobra insegura por cumplir el plazo. |
| Registro en Pasaporte Vial | EDU-DEC-001.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: Diseño EDUDRIVE.

## EDU-PRE-001.E4 Prevenir la exposición y el daño

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Prevención |
| Código de competencia | EDU-PRE-001.E4 |
| Competencia | Prevenir la exposición y el daño |
| Subcompetencia | S1 Evaluar condiciones personales; S2 Inspeccionar vehículo; S3 Ajustar o cancelar el viaje |
| Objetivo | Verificar aptitud percibida, protección y estado básico antes de conducir. |
| Contenido | Fatiga, sustancias, atención, fármacos con consulta profesional; llantas, frenos, luces y carga. |
| Actividad | Inspección con lista y caso de somnolencia o advertencia de medicamento. |
| Juego | Salida responsable: detectar razones para no salir. |
| Escenario SIMUDRIVE | SIM-PRE-001-E4: Lluvia, llanta defectuosa y cansancio; cancelar y corregir condiciones. |
| Evidencia | Lista firmada de observación y plan de contingencia. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Detecta fallas, reconoce límites y no inicia el viaje inseguro. Error crítico que impide acreditar: conducir con deterioro declarado o falla crítica. |
| Registro en Pasaporte Vial | EDU-PRE-001.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: CR02.

# Matriz curricular detallada

## EDU-CON-001.E4 Comprender y asumir la responsabilidad de conducir

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Conducción |
| Código de competencia | EDU-CON-001.E4 |
| Competencia | Comprender y asumir la responsabilidad de conducir |
| Subcompetencia | S1 Verificar habilitación aplicable; S2 Planificar práctica por vehículo; S3 Demostrar decisiones defensivas |
| Objetivo | Organizar formación y práctica acorde con clase de vehículo y requisitos vigentes. |
| Contenido | Rutas auto/moto, permiso, supervisión y acreditación oficial; EDUDRIVE no sustituye licencias. |
| Actividad | Construir expediente formativo y plan de práctica con instructor. |
| Juego | Mi ruta de conducción: elegir evidencia y requisitos. |
| Escenario SIMUDRIVE | SIM-CON-001-E4: Usuario sin habilitación intenta iniciar práctica vial; redirigir a teoría/simulación. |
| Evidencia | Plan por clase y verificación de requisitos por responsable. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Distingue requisitos, alcance de evidencia y práctica autorizada. Error crítico que impide acreditar: habilitar práctica vial por edad o curso completado. |
| Registro en Pasaporte Vial | EDU-CON-001.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: CR01, CR02.

## EDU-COV-001.E4 Convivir y proteger a otras personas

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Convivencia vial |
| Código de competencia | EDU-COV-001.E4 |
| Competencia | Convivir y proteger a otras personas |
| Subcompetencia | S1 Dar espacio y prioridad segura; S2 Gestionar conflictos sin agresión; S3 Favorecer accesibilidad |
| Objetivo | Convivir con peatones, motociclistas y ciclistas, reconociendo mayor vulnerabilidad. |
| Contenido | Sistema Seguro, cortesía, accesibilidad, convivencia laboral y protección de usuarios. |
| Actividad | Resolver incidente de prioridad y proponer mejora en acceso a centro de trabajo. |
| Juego | La vía compartida: negociar sin intimidación. |
| Escenario SIMUDRIVE | SIM-COV-001-E4: Bocina y presión tras una persona con movilidad lenta; esperar y mantener distancia. |
| Evidencia | Observación y análisis de efectos sobre otras personas. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Mantiene distancia, desescala y deja espacio accesible. Error crítico que impide acreditar: usar el vehículo para intimidar. |
| Registro en Pasaporte Vial | EDU-COV-001.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: CR03; Diseño EDUDRIVE.

# Matriz curricular detallada

## EDU-EME-001.E4 Protegerse y activar ayuda

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Emergencias |
| Código de competencia | EDU-EME-001.E4 |
| Competencia | Protegerse y activar ayuda |
| Subcompetencia | S1 Detenerse o resguardarse de forma segura; S2 Comunicar emergencia; S3 Evitar riesgos secundarios |
| Objetivo | Resolver avería o siniestro priorizando protección y activación de ayuda. |
| Contenido | Ubicación, señalización si puede hacerse sin exposición, 9-1-1 y asistencia; límites de intervención. |
| Actividad | Ensayo de reporte y decisión de permanencia/refugio según el contexto. |
| Juego | Primero la protección: ordenar acciones ante avería. |
| Escenario SIMUDRIVE | SIM-EME-001-E4: Avería con visibilidad reducida; elegir posición protegida y pedir ayuda sin caminar por carril. |
| Evidencia | Lista de acciones y llamada ficticia; no llamadas reales de ensayo. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Selecciona protección contextual, reporta y evita nuevos conflictos. Error crítico que impide acreditar: exponerse al tránsito para señalizar o auxiliar. |
| Registro en Pasaporte Vial | EDU-EME-001.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: Diseño EDUDRIVE.

## EDU-SOS-001.E4 Elegir movilidad segura y sostenible

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Movilidad sostenible |
| Código de competencia | EDU-SOS-001.E4 |
| Competencia | Elegir movilidad segura y sostenible |
| Subcompetencia | S1 Elegir el modo adecuado; S2 Conducir con eficiencia segura; S3 Revisar impacto y accesibilidad |
| Objetivo | Planificar movilidad que combine seguridad, acceso y uso responsable de recursos. |
| Contenido | Viajes evitables, transporte público, conducción suave, mantenimiento y restricciones personales. |
| Actividad | Comparar dos semanas o dos planes de viaje con datos voluntarios. |
| Juego | Movilidad posible: optimizar sin sacrificar protección. |
| Escenario SIMUDRIVE | SIM-SOS-001-E4: Viaje corto con ruta peatonal accesible; comparar caminar, bus y auto sin imponer una opción. |
| Evidencia | Plan comparado e indicador de cambio con límites. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Justifica el modo y mantiene márgenes durante conducción eficiente. Error crítico que impide acreditar: reducir seguridad para ahorrar tiempo o combustible. |
| Registro en Pasaporte Vial | EDU-SOS-001.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Común |

Referencias: Diseño EDUDRIVE.

# Matriz curricular detallada

## EDU-CON-002.E4 Controlar un automóvil con seguridad

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Conducción |
| Código de competencia | EDU-CON-002.E4 |
| Competencia | Controlar un automóvil con seguridad |
| Subcompetencia | S1 Preparar puesto y controles; S2 Maniobrar a baja velocidad; S3 Estacionar y asegurar |
| Objetivo | Demostrar control de automóvil en espacio habilitado y con instructor. |
| Contenido | Asiento, espejos, cinturón, pedales, transmisión, arranque, reversa y estacionamiento. |
| Actividad | Práctica progresiva de salida, parada, reversa y estacionamiento. |
| Juego | Garaje seguro: planificar maniobra antes de ejecutarla. |
| Escenario SIMUDRIVE | SIM-CON-002-E4: Salida en reversa con obstáculo oculto; comprobar entorno y detenerse ante incertidumbre. |
| Evidencia | Rúbrica del instructor en vehículo y registro simulado separado. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Mantiene control en las tres maniobras y asegura el vehículo al finalizar. Error crítico que impide acreditar: moverse sin comprobar entorno o perder control. |
| Registro en Pasaporte Vial | EDU-CON-002.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Automóvil |

Referencias: CR01, CR02.

## EDU-CON-003.E4 Controlar una motocicleta con seguridad

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Conducción |
| Código de competencia | EDU-CON-003.E4 |
| Competencia | Controlar una motocicleta con seguridad |
| Subcompetencia | S1 Preparar equipo y motocicleta; S2 Controlar equilibrio y frenado; S3 Elegir posición y maniobra |
| Objetivo | Demostrar control de motocicleta con protección y enseñanza práctica específica. |
| Contenido | Casco/equipo, inspección, mandos, equilibrio, frenado progresivo, curvas y visibilidad. |
| Actividad | Circuito cerrado con instructor: arrancar, detenerse y cambiar dirección. |
| Juego | Dos ruedas y margen: elegir preparación y trayectoria. |
| Escenario SIMUDRIVE | SIM-CON-003-E4: Superficie deslizante en curva; ajustar antes de entrar y evitar maniobra brusca. |
| Evidencia | Observación en moto, condiciones de superficie y límites del simulador. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Prepara equipo, conserva estabilidad y ajusta maniobra con anticipación. Error crítico que impide acreditar: salir sin protección o perder estabilidad por acción insegura. |
| Registro en Pasaporte Vial | EDU-CON-003.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Motocicleta |

Referencias: CR01, CR02.

# Matriz curricular detallada

## EDU-CON-004.E4 Resolver intersecciones y rotondas

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Conducción |
| Código de competencia | EDU-CON-004.E4 |
| Competencia | Resolver intersecciones y rotondas |
| Subcompetencia | S1 Interpretar prioridad; S2 Elegir carril y señalizar; S3 Ejecutar o cancelar giro |
| Objetivo | Resolver intersecciones y rotondas conforme a reglas locales y trayectorias de otros. |
| Contenido | Prioridad, carril, espejos, puntos ciegos, señalización y salida; variantes auto/moto. |
| Actividad | Maqueta de prioridad seguida de simulación y práctica habilitada. |
| Juego | Rotonda razonada: explicar entrada y salida. |
| Escenario SIMUDRIVE | SIM-CON-004-E4: Salida perdida en rotonda; continuar por opción permitida sin cortar carriles. |
| Evidencia | Decisiones etiquetadas por vehículo y observación de giros. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Respeta prioridad, conserva carril permitido y cancela si falta margen. Error crítico que impide acreditar: cortar trayectoria para alcanzar una salida. |
| Registro en Pasaporte Vial | EDU-CON-004.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Auto o moto por separado |

Referencias: CR02.

## EDU-CON-005.E4 Conducir en vías y condiciones diversas

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Conducción |
| Código de competencia | EDU-CON-005.E4 |
| Competencia | Conducir en vías y condiciones diversas |
| Subcompetencia | S1 Adaptar velocidad y distancia; S2 Incorporarse y cambiar carril; S3 Replanificar ante clima o vía adversa |
| Objetivo | Resolver vías rápidas y rurales manteniendo visibilidad y margen de seguridad. |
| Contenido | Incorporación, adelantamiento, curvas, pendientes, noche, lluvia y usuarios rurales; no cruzar zonas inundadas. |
| Actividad | Comparar variantes de una ruta y practicar según nivel y habilitación. |
| Juego | Ruta con margen: elegir esperar, desviar o continuar. |
| Escenario SIMUDRIVE | SIM-CON-005-E4: Curva rural con lluvia y vehículo lento; conservar distancia y posponer adelantamiento. |
| Evidencia | Decisiones y práctica según vehículo; registrar condiciones observadas. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Ajusta antes del riesgo, comprueba espacio y renuncia a maniobra incierta. Error crítico que impide acreditar: adelantar sin visibilidad o entrar en zona inundada. |
| Registro en Pasaporte Vial | EDU-CON-005.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Auto o moto por separado |

Referencias: CR02.

# Matriz curricular detallada

## EDU-CON-006.E4 Aplicar conocimiento para la preparación teórica

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Conducción |
| Código de competencia | EDU-CON-006.E4 |
| Competencia | Aplicar conocimiento para la preparación teórica |
| Subcompetencia | S1 Ubicar fuente oficial por clase; S2 Resolver problemas normativos; S3 Explicar y corregir errores |
| Objetivo | Preparar evaluación teórica diferenciada mediante comprensión y casos inéditos. |
| Contenido | Manuales oficiales de auto/moto y normativa vigente; trazabilidad por tema, fuente y versión. |
| Actividad | Resolver casos inéditos y revisar cada error con referencia oficial. |
| Juego | La razón de la regla: elegir respuesta y justificarla. |
| Escenario SIMUDRIVE | SIM-CON-006-E4: Caso de prioridad cambia de vehículo y condición; aplicar regla sin memorizar posición. |
| Evidencia | Resoluciones razonadas y reporte diagnóstico por competencia. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Justifica los casos trabajados y corrige errores con fuente verificada; no equivale a aprobar prueba oficial. Error crítico que impide acreditar: sostener una respuesta que autoriza conducta peligrosa. |
| Registro en Pasaporte Vial | EDU-CON-006.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Auto o moto por separado |

Referencias: CR01, CR02.

## EDU-PRE-002.E4 Mantener y actualizar la movilidad segura

| Campo | Definición curricular |
| --- | --- |
| Etapa | CONDUZCO |
| Edad | 17+ |
| Dominio | Prevención |
| Código de competencia | EDU-PRE-002.E4 |
| Competencia | Mantener y actualizar la movilidad segura |
| Subcompetencia | S1 Reconocer cambios en necesidades; S2 Solicitar evaluación o apoyo; S3 Revisar plan de movilidad |
| Objetivo | Actualizar competencias tras pausa, cambio de vehículo o nuevas necesidades durante la vida. |
| Contenido | Retorno a conducción, cambios funcionales sin diagnóstico automático, apoyos y alternativas de movilidad. |
| Actividad | Entrevista formativa, diagnóstico práctico habilitado y plan de actualización individual. |
| Juego | Mi movilidad cambia: elegir apoyos y alternativas. |
| Escenario SIMUDRIVE | SIM-PRE-002-E4: Retorno tras años sin conducir y ruta desconocida; practicar con apoyo antes del viaje complejo. |
| Evidencia | Plan individual, evidencia reciente y derivación profesional cuando corresponda. |
| Evaluación | Rúbrica S1–S3 y protocolo P4; observación y explicación de la decisión. Variar el contexto. |
| Criterio de dominio | Protocolo P4. Reconoce límites y elige apoyo o alternativa sin inferir incapacidad por edad. Error crítico que impide acreditar: ignorar una limitación identificada para seguir conduciendo. |
| Registro en Pasaporte Vial | EDU-PRE-002.E4; S1–S3; contexto, apoyo, modalidad y evidencias. Aplicar registro PV y estado CTM; alcance limitado a lo observado. |
| Ruta de formación | Aprendizaje permanente |

Referencias: Diseño EDUDRIVE.

# Fuentes y alcance de la verificación

Fuentes consultadas el 25 de septiembre de 2026. Los enlaces T01–T18 aparecen junto a cada unidad comparada. Los mapas y las páginas de las unidades sustentan la referencia conceptual; los criterios numéricos de evaluación pertenecen a EDUDRIVE.

**R01** [THINK! Curriculum map 3–6](https://www.think.gov.uk/resource/curriculum-map-3-6/). Mapa oficial y PDF; seis unidades y objetivos por edad.

**R02** [THINK! Curriculum map 7–12](https://www.think.gov.uk/resource/curriculum-map-7-12/). Mapa oficial y PDF; seis unidades, autonomía contextual y rutas.

**R03** [THINK! Curriculum map 13–16](https://www.think.gov.uk/resource/curriculum-map-13-16/). Mapa oficial y PDF; seis unidades, responsabilidad y cambio de hábitos.

**CR01** [MOPT y DGEV — Manuales específicos por tipo de vehículo](https://www.mopt.go.cr/node/1230). Comunicado del 6 de noviembre de 2025: diferenciación de pruebas A/B a partir del 2 de marzo de 2026. Sustenta separación de rutas; no un mapeo completo de manuales.

La unidad Campaign spotlight se identifica y contrasta mediante el mapa 7–12 y el catálogo oficial; su página individual no respondió durante la consulta. Este hecho no cambia la identificación de las seis unidades principales.

# Referencias de Costa Rica y control de publicación

**CR02** [COSEVI — Ley 9078 y sus reformas](https://www.csv.go.cr/documents/20126/0/Ley%2Bde%2BTr%C3%A1nsito%2Bpor%2BV%C3%ADas%2BP%C3%BAblicas%2BTerrestres%2By%2BSeguridad%2BVial%2BNo.%2B9078%2By%2Bsus%2Breformas.pdf/9abd05d2-5c2e-4135-f158-f3b4e5f13443?t=1619203070697). Copia institucional consultada, arts. 83–85 sobre permiso, licencia y excepción A1. Para cada publicación operativa comprobar texto vigente en SCIJ/SINALEVI; no inferir vigencia de todas las reformas a partir del nombre del PDF.

**CR03** [COSEVI — Programas](https://www.csv.go.cr/programas). Centros Educativos Seguros, Asistencia Municipal Vial y Empresas Seguras: contexto de intervención.

**R04** [SCIJ y SINALEVI — Ficha de la Ley 9078](https://sinalevi.go.cr/ResultadosNormativa/Informacion?param1=73504&param2=&param3=2&param4=). Punto de control normativo. La ficha dinámica no expuso el texto completo en esta consulta; las citas de artículos se apoyan en CR02.

**R05** [Conversación de referencia EDUDRIVE](chatgpt-conversation://6ab6618c-1818-83e8-b0a9-3804cee229b4). Planes de seguridad vial: antecedente de etapas, componentes y propósito. No es fuente de autoridad legal ni evidencia de implementación.

## Qué exige una validación posterior

La copia de la Ley 9078 permite documentar la separación entre etapa pedagógica y autorización legal, pero la normativa puede cambiar. Antes de un recurso que indique edad exacta, categoría, distancia reglamentaria, requisitos de protección o procedimiento administrativo, el especialista debe comprobar la versión vigente y anotar su fuente. Las fichas definen competencias; no sustituyen un manual legal.

Emergencias se limita a protección propia, comunicación y seguimiento de instrucciones de servicios competentes. No se especifican tratamientos, rescates ni procedimientos clínicos. Un futuro módulo práctico de primeros auxilios requiere diseño y validación profesional propios.

## Registro de versión

| Versión | Fecha | Contenido |
| --- | --- | --- |
| 1.0.0 | 25 de septiembre de 2026 | Primera arquitectura integrada: 4 etapas, 11 dominios, 50 desempeños, 150 subcompetencias, 18 comparaciones y contratos curriculares de integración. |

Aprobación institucional: no registrada en esta edición. Responsable curricular y fecha efectiva se completarán mediante el proceso de adopción de EDUDRIVE, sin alterar los registros históricos de esta versión.
