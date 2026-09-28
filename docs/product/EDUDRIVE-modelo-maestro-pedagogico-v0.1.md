# Modelo Maestro Pedagógico de EDUDRIVE

## Estado y propósito

Versión: 0.1  
Estado: propuesta para validación  
Ámbito inicial: Costa Rica con núcleo universal  

Este documento convierte la visión del EDUDRIVE Framework en reglas concretas para diseñar, producir, publicar y evaluar experiencias educativas dentro de `edudrive-api`.

EDUDRIVE no se organizará principalmente alrededor de cursos terminados. Se organizará alrededor de la evolución verificable del Ciudadano Vial. Los cursos, misiones, prácticas y laboratorios son vehículos pedagógicos; la competencia y su evidencia constituyen el resultado permanente.

## 1. Resultado que debe producir EDUDRIVE

EDUDRIVE deberá ayudar a cada persona a:

1. comprender el sistema de movilidad y su papel dentro de él;
2. reconocer peligros y anticipar consecuencias;
3. tomar decisiones que prioricen la vida;
4. practicar comportamientos seguros en contextos adecuados a su edad;
5. demostrar competencias mediante evidencia diversa;
6. reflexionar sobre errores sin recibir etiquetas negativas;
7. mantener y actualizar sus capacidades durante toda la vida.

El producto no deberá afirmar únicamente que una persona consumió contenido o aprobó una prueba. Deberá poder explicar qué conducta desarrolló, qué evidencia la respalda, en qué contexto se observó, cuándo ocurrió y qué tan confiable es la conclusión.

## 2. Principios no negociables

1. La vida tiene prioridad sobre la velocidad, la conveniencia o la puntuación.
2. Toda persona es un Ciudadano Vial, aunque nunca conduzca un vehículo.
3. La pedagogía determina la tecnología y no al contrario.
4. Toda experiencia debe estar vinculada con competencias e indicadores identificables.
5. Toda actualización del Pasaporte Vial debe tener evidencia rastreable.
6. Una respuesta correcta no equivale por sí sola a una conducta dominada.
7. Los errores son oportunidades de aprendizaje, no rasgos permanentes de la persona.
8. La edad orienta la experiencia; el dominio demostrado orienta el progreso.
9. La accesibilidad, dignidad, privacidad y protección de menores forman parte del diseño.
10. La IA puede recomendar y explicar, pero no inventar evidencia ni certificar por sí sola.

## 3. Arquitectura curricular territorial

Cada experiencia se compondrá de dos capas explícitas.

### 3.1 Núcleo universal

Incluye capacidades aplicables en distintos países:

- respeto por la vida y convivencia;
- observación y atención;
- percepción y gestión del riesgo;
- toma de decisiones;
- comunicación entre actores viales;
- movilidad activa, sostenible e inclusiva;
- uso de protección personal y sistemas de retención;
- prevención de distracciones, fatiga y consumo de sustancias;
- respuesta inicial ante incidentes;
- adaptación a clima, infraestructura y condiciones cambiantes.

### 3.2 Perfil Costa Rica

Incluye contenido que debe versionarse cuando cambie la fuente normativa:

- Ley de Tránsito por Vías Públicas Terrestres y Seguridad Vial, Ley 9078;
- señalización y reglas oficiales aplicables en Costa Rica;
- instituciones y canales de atención nacionales;
- movilidad urbana y rural costarricense;
- autobuses, motocicletas, bicicletas y otros patrones de movilidad relevantes;
- rutas escolares y entornos de centros educativos;
- escenarios, lenguaje y decisiones propias del contexto nacional.

Todo dato normativo deberá conservar fuente, jurisdicción, fecha de revisión, responsable editorial y versión. El contenido universal no deberá presentarse como ley costarricense, y la normativa costarricense no deberá presentarse como una regla universal.

## 4. Trayectoria EDUDRIVE 5–80

Las edades son orientativas. Una institución o mentor podrá ajustar la ruta sin eliminar requisitos de protección infantil o seguridad.

| Etapa | Edad orientativa | Identidad pedagógica | Resultado principal | Experiencias dominantes |
|---|---:|---|---|---|
| Explorar | 5–8 | Explorador Vial | Reconocer espacios, actores y rutinas seguras | historias, personajes, canciones, clasificación visual, práctica acompañada |
| Descubrir | 9–12 | Aventurero Vial | Ganar autonomía como peatón, pasajero y ciclista | misiones, mapas, observación comunitaria, juegos de decisión |
| Comprender | 13–15 | Aprendiz Vial | Interpretar riesgo, presión social y consecuencias | dilemas, investigación, debates, simulaciones de decisiones |
| Prepararse | 16–18 | Aspirante Responsable | Integrar normativa, riesgo y responsabilidad previa a conducir | casos, planificación, laboratorios introductorios, reflexión |
| Conducir | 18–25 | Conductor Responsable | Consolidar decisiones y hábitos seguros | práctica deliberada, SIMUDRIVE, retroalimentación y reintentos |
| Perfeccionar | 25–60 | Ciudadano Vial Experimentado | Revalidar y especializar competencias | escenarios complejos, actualización, conducción profesional y mentoría familiar |
| Actualizar | 60+ | Ciudadano Vial Activo | Mantener movilidad segura, autonomía y adaptación | autoevaluación, actualización tecnológica, planificación de alternativas |
| Enseñar | Mentores | Mentor EDUDRIVE | Facilitar aprendizaje y producir observaciones confiables | guías, rúbricas, calibración, observación y acompañamiento |

Una competencia podrá aparecer en varias etapas con diferente profundidad. Por ejemplo, “cruzar una vía de forma segura” evoluciona desde seguir una rutina acompañada hasta evaluar visibilidad, velocidad, infraestructura, accesibilidad y comportamiento de terceros.

## 5. Estructura oficial de aprendizaje

Toda experiencia deberá declarar la siguiente trazabilidad:

```text
Principio
  -> Dominio
    -> Macrocompetencia
      -> Competencia
        -> Microcompetencia
          -> Indicador observable
            -> Evidencia
```

### 5.1 Definiciones operativas

- **Dominio:** dimensión permanente de la movilidad segura.
- **Macrocompetencia:** agrupación de capacidades funcionales relacionadas.
- **Competencia:** capacidad de integrar conocimiento, criterio y acción en un contexto.
- **Microcompetencia:** capacidad específica que puede practicarse y observarse.
- **Indicador:** conducta o resultado que permite valorar una microcompetencia.
- **Evidencia:** registro verificable producido por una experiencia.
- **Estado de dominio:** interpretación pedagógica del desempeño acumulado.
- **Confianza:** certeza de que el estado asignado representa una capacidad estable y transferible.

### 5.2 Dominios canónicos provisionales

El catálogo `ECS-001` define actualmente diez dominios:

1. Cultura y Ciudadanía Vial.
2. Percepción y Atención.
3. Toma de Decisiones.
4. Control del Vehículo.
5. Gestión del Riesgo.
6. Comunicación Vial.
7. Normativa y Regulación.
8. Movilidad Sostenible.
9. Tecnología y Movilidad Inteligente.
10. Gestión de Emergencias.

Existe una inconsistencia documental que debe resolverse antes de declarar la versión 1.0: el README principal enumera once dominios iniciales con nombres diferentes, mientras `ECS-001` registra diez dominios más desarrollados. Hasta que el Framework publique una decisión explícita, `ECS-001` será la referencia provisional para implementación y ningún identificador deberá renumerarse.

## 6. Unidad mínima publicable

La unidad mínima del producto no será una página de texto. Será una **Experiencia Educativa Trazable**.

Una experiencia no podrá marcarse como lista para producción si no contiene:

1. público y etapa vital;
2. contexto territorial;
3. competencia, microcompetencia e indicadores;
4. propósito expresado como comportamiento seguro;
5. diagnóstico o activación de conocimiento previo;
6. explicación adecuada a la edad;
7. demostración visual o modelado;
8. práctica con decisión significativa;
9. retroalimentación específica para cada resultado relevante;
10. oportunidad de reintento o transferencia;
11. instrumento de evidencia;
12. regla de interpretación de la evidencia;
13. actualización esperada del Pasaporte Vial;
14. criterios de accesibilidad;
15. fuente y revisión editorial cuando exista contenido normativo;
16. telemetría mínima necesaria y propósito de cada dato.

## 7. Modelo de experiencias

EDUDRIVE combinará experiencias; no todas deben convertirse en video o cuestionario.

| Tipo | Propósito | Evidencia posible |
|---|---|---|
| Historia interactiva | Comprender consecuencias y empatía | decisiones, justificaciones, revisión posterior |
| Exploración visual | Detectar actores, señales y peligros | aciertos, omisiones, tiempo razonable, patrones |
| Clasificación | Construir conceptos y relaciones | categorías seleccionadas y correcciones |
| Ordenamiento | Aprender secuencias seguras | orden, omisiones, reintentos |
| Dilema vial | Ejercitar juicio ético y situacional | opción, explicación y cambio tras retroalimentación |
| Observación comunitaria | Transferir al entorno real | registro guiado, evidencia aprobada por mentor |
| Práctica acompañada | Ejecutar una conducta fuera de pantalla | rúbrica de observación y reflexión del participante |
| Micro-simulación web | Practicar decisiones dinámicas simples | eventos, tiempos, omisiones y consistencia |
| Laboratorio SIMUDRIVE | Practicar comportamiento complejo | telemetría, decisiones, contexto y repetición |
| Desafío de competencia | Integrar conocimientos y decisiones | desempeño por indicador, no solo nota global |
| Reflexión | Desarrollar conciencia sobre la propia conducta | explicación, comparación antes/después, compromiso |

## 8. Evaluación basada en evidencias

### 8.1 Fuentes de evidencia

- comprensión declarativa;
- decisión ante escenarios;
- desempeño en práctica guiada;
- observación de mentor calibrado;
- simulación;
- transferencia en diferentes contextos;
- repetición consistente;
- revalidación posterior.

### 8.2 Dimensiones mínimas

Cada evidencia deberá registrar, cuando aplique:

- competencia e indicador asociados;
- fuente y experiencia que la produjo;
- actor observado y observador autorizado;
- fecha y contexto;
- resultado y calidad;
- nivel de dificultad;
- condiciones relevantes;
- intentos y retroalimentación;
- versión del contenido o escenario;
- vigencia y reglas de revalidación;
- trazabilidad hacia datos originales.

### 8.3 Estado y confianza

El estado de dominio y la confianza no deberán confundirse. Una ejecución correcta aislada puede elevar el estado de forma limitada, pero deberá mantener una confianza baja. La confianza aumentará con diversidad de fuentes, consistencia, dificultad, actualidad y transferencia entre contextos.

Ninguna fórmula global deberá aplicarse de manera idéntica a todas las competencias. La revisión del punto ciego, la convivencia como pasajero y la respuesta ante emergencias tienen riesgos, frecuencias y evidencias diferentes.

## 9. Pasaporte Vial

El Pasaporte Vial será la vista longitudinal comprensible del desarrollo, no una colección de cursos y medallas.

Deberá mostrar:

- etapa y ruta actual;
- competencias consolidadas, en desarrollo y por revalidar;
- confianza por competencia;
- evidencias principales y su origen;
- experiencias, prácticas y laboratorios;
- fortalezas y oportunidades de aprendizaje;
- historial de cambios;
- recomendaciones explicables;
- certificaciones cuando correspondan;
- controles de privacidad y personas autorizadas.

Para menores de edad deberá existir una presentación apropiada para el niño, una vista diferenciada para responsables autorizados y otra para mentores o instituciones. Estas vistas no deberán revelar datos innecesarios ni convertir el Pasaporte en un mecanismo de vigilancia o castigo.

## 10. Learning OS

El Learning OS deberá elegir la siguiente experiencia utilizando:

- etapa vital y contexto;
- objetivos de aprendizaje;
- prerrequisitos;
- estado y confianza de las competencias;
- evidencias recientes;
- errores recurrentes;
- accesibilidad y preferencias autorizadas;
- obligaciones institucionales;
- necesidad de refuerzo, transferencia o revalidación.

Toda recomendación deberá incluir una explicación comprensible. El sistema deberá distinguir entre una recomendación, una obligación institucional y un requisito de seguridad.

## 11. Protección infantil y accesibilidad

Para las etapas infantiles se aplicarán al menos estas reglas:

1. instrucciones breves, concretas y acompañadas visualmente;
2. ausencia de patrones manipulativos, publicidad o presión competitiva;
3. retroalimentación que corrija la decisión sin avergonzar;
4. recopilación mínima de datos;
5. consentimiento y relación de responsable cuando corresponda;
6. alternativas a audio, color, movimiento, lectura y precisión motora;
7. animaciones pausables y sin destellos peligrosos;
8. actividades fuera de pantalla únicamente con acompañamiento apropiado;
9. recompensas centradas en hábitos, cooperación y esfuerzo reflexivo;
10. revisión pedagógica y de seguridad antes de publicar.

## 12. Piloto vertical recomendado

### 12.1 Nombre

**Misión Camino Seguro: cruzar una vía de forma segura**

### 12.2 Etapa inicial

Explorar, 5–8 años, con una variante posterior para Descubrir, 9–12 años.

### 12.3 Razón de selección

Permite validar el modelo completo sin depender de un vehículo ni de SIMUDRIVE. Es relevante para escuelas costarricenses, admite práctica acompañada y conecta observación, percepción del riesgo, toma de decisiones, comunicación y cultura vial.

### 12.4 Secuencia

1. **Historia inicial:** un personaje debe llegar a la escuela y encuentra distintos cruces.
2. **Exploración:** identificar acera, borde seguro, vehículos, obstáculos y sitios de cruce.
3. **Demostración animada:** detenerse, observar, escuchar, confirmar y cruzar caminando.
4. **Práctica guiada:** escoger entre varios puntos de cruce y justificar visualmente la elección.
5. **Variación:** vehículo estacionado que bloquea visibilidad, lluvia y zona rural sin acera.
6. **Actividad acompañada:** observar con un adulto un cruce real sin ingresar a la calzada.
7. **Reflexión:** explicar qué cambió en la decisión del personaje.
8. **Desafío:** resolver tres escenarios nuevos, sin memorizar una única imagen.
9. **Evidencia:** registrar indicadores separados de observación, selección del lugar y secuencia.
10. **Pasaporte:** mostrar progreso y confianza inicial, sin declarar dominio por una sola sesión.

### 12.5 Indicadores iniciales

- identifica un lugar con mejor visibilidad;
- se detiene antes de entrar en la zona de circulación;
- observa las direcciones relevantes;
- reconoce obstáculos que limitan la visión;
- evita correr durante el cruce;
- solicita acompañamiento cuando el escenario supera su autonomía;
- transfiere la secuencia a una imagen o situación diferente.

### 12.6 Evidencias del piloto

- decisiones en cuatro escenarios visuales;
- omisiones y correcciones tras retroalimentación;
- desempeño en escenarios de transferencia;
- reflexión breve por voz, selección visual o texto según accesibilidad;
- observación opcional de una práctica acompañada;
- repetición posterior para medir retención.

## 13. Arquitectura de contenidos requerida en la aplicación

El modelo actual de módulo, unidad, lección y bloque deberá evolucionar sin eliminar compatibilidad. Cada experiencia necesitará metadatos adicionales:

- etapa vital y rango orientativo;
- jurisdicción y versión normativa;
- competencias, microcompetencias e indicadores vinculados;
- objetivos conductuales;
- tipo de experiencia;
- reglas de evidencia;
- variantes de accesibilidad;
- recursos y animaciones versionados;
- retroalimentación por decisión;
- requisitos de mentor o responsable;
- reglas de desbloqueo, transferencia y revalidación.

También harán falta nuevos bloques interactivos para escenarios, selección espacial, ordenamiento, clasificación, reflexión, práctica acompañada y animación controlada. El botón “Marcar como completada” deberá dejar de ser la evidencia principal: la finalización deberá derivarse de la experiencia y sus eventos verificables.

## 14. Gobernanza de producción

Cada experiencia pasará por estas revisiones:

1. diseño pedagógico;
2. precisión vial y normativa;
3. adecuación por edad;
4. accesibilidad;
5. protección de menores y privacidad;
6. diseño audiovisual e interacción;
7. instrumentación de evidencias;
8. control técnico;
9. prueba con participantes;
10. aprobación y publicación versionada.

Los roles de autor, revisor normativo, revisor pedagógico y aprobador deberán quedar registrados. Una actualización normativa deberá identificar las experiencias afectadas y activar su revisión.

## 15. Medición del impacto

El éxito no se medirá solamente con finalización, permanencia o nota. El piloto deberá medir:

- reconocimiento inicial y posterior de peligros;
- mejora de decisiones ante escenarios nuevos;
- reducción de omisiones críticas;
- retención después de un intervalo;
- transferencia a práctica acompañada;
- comprensión de la retroalimentación;
- accesibilidad y abandono por barreras;
- percepción de seguridad, utilidad y dignidad;
- calidad y suficiencia de la evidencia obtenida.

## 16. Decisiones necesarias antes de la versión 1.0

1. Resolver la lista canónica de dominios entre README y `ECS-001`.
2. Publicar competencias, microcompetencias e indicadores del piloto con identificadores estables.
3. Definir estados de dominio y reglas iniciales de confianza por tipo de competencia.
4. Definir la gobernanza de normativa costarricense y frecuencia de revisión.
5. Aprobar la taxonomía de etapas e identidades pedagógicas.
6. Definir el modelo de consentimiento y vistas del Pasaporte para menores.
7. Seleccionar tecnologías de animación e interacción accesibles para web y móvil.
8. Definir el formato canónico de paquetes de experiencia y sus versiones.
9. Establecer el proceso de validación con docentes, familias, especialistas y participantes.

## 17. Plan de ejecución inmediato

### Incremento 1: contrato pedagógico y datos

- modelar Experience, Indicator y EvidenceRule;
- vincular contenido con competencias;
- incorporar etapa, jurisdicción y versión;
- registrar eventos de interacción sin interpretarlos todavía como dominio.

### Incremento 2: reproductor de experiencias

- renderizar texto enriquecido de forma segura;
- incorporar animaciones accesibles;
- añadir selección visual, clasificación, ordenamiento y reflexión;
- ofrecer retroalimentación por decisión y reintentos.

### Incremento 3: piloto Camino Seguro

- producir guion, storyboard y recursos;
- implementar escenarios y práctica;
- generar evidencia por indicador;
- mostrar resultados iniciales en el Pasaporte Vial.

### Incremento 4: interpretación y Learning OS

- calcular estado inicial por competencia;
- separar dominio y confianza;
- generar recomendaciones explicables;
- programar refuerzo, transferencia y revalidación.

### Incremento 5: validación

- ejecutar piloto controlado;
- revisar comprensión, accesibilidad y seguridad;
- ajustar contenido y reglas;
- documentar resultados antes de escalar a nuevas rutas.

## 18. Fuentes base

### EDUDRIVE

- `edudrive-framework/README.md`.
- `docs/catalog/ECS-001-competency-catalog.md`.
- `docs/specifications/EDF-001-learning-os-specification.md`.
- `docs/specifications/EDF-002-competency-framework-specification.md`.
- `docs/specifications/EDF-003_Arquitectura_del_Dominio_EDUDRIVE_v0.1.md`.
- `docs/curriculum/index.md`.
- `docs/pasaporte-vial/index.md`.

### Costa Rica

- [Ministerio de Educación Pública, programa Camino Seguro a la Escuela](https://mep.go.cr/programas-proyectos/camino-seguro).
- [Consejo de Seguridad Vial, programa Centros Educativos Seguros](https://www.csv.go.cr/programa-de-centros-educativos-seguros).
- [Ministerio de Educación Pública, programa de Educación Cívica de tercer ciclo y educación diversificada](https://www.mep.go.cr/sites/default/files/media/civica3ciclo_diversificada.pdf).
- [Sistema Costarricense de Información Jurídica, Ley 9078 y sus versiones vigentes](https://pgrweb.go.cr/Scij/Busqueda/Normativa/Normas/nrm_texto_completo.aspx?nValor1=1&nValor2=73504&param1=NRM&strTipM=FN).

### Internacional

- [Organización Mundial de la Salud, seguridad vial de niños y jóvenes](https://www.who.int/health-topics/road-safety/children-and-young-people).
- [Organización Mundial de la Salud, diez estrategias para mantener seguros a los niños en las vías](https://www.who.int/publications/i/item/ten-strategies-for-keeping-children-safe-on-the-road).

## 19. Criterio de salida

El siguiente incremento no deberá consistir en llenar los cursos EDU-101 y EDU-102 con texto adicional. Deberá entregar una experiencia vertical del piloto que produzca eventos, evidencias por indicador y una actualización explicable del Pasaporte Vial.
