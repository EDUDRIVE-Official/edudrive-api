EDF
Revisión curricular 01

---

Matriz EDUDRIVE 3-80 y materiales derivados
25 de septiembre de 2026 · Informe v1.0.0

Conclusión: existe una base curricular amplia, pero todavía no está cerrada. Corresponde corregir coherencia, completar decisiones de arquitectura y revisar los materiales antes de organizar pruebas. Los pilotos, controles y ensayos producidos se conservan como borradores de una fase posterior.

| Qué se comprobó | Resultado documental |
| --- | --- |
| Estructura de la matriz | 50 códigos únicos; 150 S; 15 campos solicitados presentes en cada fila. |
| Etapas y dominios | 11 fichas en E1, 11 en E2, 11 en E3 y 17 en E4; 11 dominios transversales. |
| Referencia THINK! | 18 unidades identificadas, seis por franja. Correspondencia de títulos confirmada con los mapas oficiales. |
| Desarrollo didáctico localizado | 5 experiencias para 4 códigos diferentes. Un kit imprimible, PIL-01. |
| Evidencia empírica | No hay aplicación de campo ni validación del instrumento documentadas. |

Qué conservar

Las cuatro etapas, la filosofía 3-80, la progresión por desempeño, la separación auto/moto, los límites de modalidad y la prohibición de equiparar un curso completado con dominio tienen continuidad en los documentos. Las S y errores críticos de las cinco experiencias coinciden con sus fichas de origen.

Alcance de esta revisión

Comprobación estructural de las 50 filas; revisión de coherencia de reglas, perfiles, componentes y materiales disponibles, con hallazgos específicos. No es una validación psicométrica, una auditoría legal completa ni una revisión exhaustiva de todos los futuros recursos. No se cambia edudrive-api.

1. Problemas que deben corregirse

---

H01 · Perfil adulto sin conducción exige conducir

Matriz: Perfiles de salida, Movilidad permanente sin conducción; PED E4 S2, PRE E4 S2, SOS E4 S2 y criterios de PAS/COV E4.

Separar alcance por rol y formular observables para adulto no conductor; no marcar una S de conducción aprobada o cumplida por omisión.

H02 · Una S carece de evidencia explícita en su actividad

EDU-SOS-001.E1.S3: Cuidar el espacio común. Actividad y evidencia se centran en elegir modo/ruta.

Añadir conducta observable de cuidado del espacio, evidencia y anclas de rúbrica; separar de la elección de ruta.

H03 · Señal prevista de la tarea frente a pista de respuesta

EDU-CIC-001.E1.S2: Frenar a una indicación; regla general sin pistas y límite 1 cuando la indicación resuelve la decisión.

Declarar antes de evaluar las señales que son estímulos de la tarea; diferenciarlas de pistas y de intervención de protección.

H04 · Número de evaluadores en retención

Piloto, Indicadores: doble valoración en A/B/C y seguimientos. Kit PIL-01, F02: permite R con un solo observador.

En este piloto, dos evaluadores independientes también en R. Una toma con uno no cierra el seguimiento previsto.

H05 · F03 designa documentos diferentes

Piloto F03 = Seguimiento individual; kit F03 = Registro en Pasaporte Vial.

Usar identificadores completos de documento, formulario y versión; no resolver referencias por F03 aislado.

H01-H03 afectan el cierre curricular. H04-H05 son inconsistencias de los derivados: su corrección no requiere esperar a que se aplique un piloto. Las páginas siguientes dejan el texto preparado para integrarlo sin alterar los documentos históricos de forma silenciosa.

2. Vacíos y límites de lo entregado

---

H06 · C2 mezcla evidencia individual y conjunto de dominio

Matriz, CTM: C0-C3 en calidad de evidencia; C2 exige protocolo completo. Un intento válido con P1 incompleto no queda caracterizado por esa escala.

Separar validez/procedencia del intento y confianza de la afirmación curricular. Mantener el intento verificable aunque no conceda dominio.

H07 · Transiciones y alcance de la afirmación no están formalizados

Matriz, CTM y Pasaporte: enumera estados y alcance, sin una tabla completa de precedencias y unidad de decisión por rol/vehículo/modalidad.

Identificar cada afirmación por persona, competencia, versión y alcance; fijar precedencias ante contradicción, vencimiento y retención.

H08 · Ficha curricular no equivale a lección lista

50 fichas; cinco experiencias para cuatro códigos distintos; kit imprimible solo de PIL-01.

Mantener inventario por código y producir recursos a partir de fichas revisadas. No medir avance por número de PDF.

H09 · Falta registro de reglas costarricenses verificadas

La matriz exige registro normativo, pero no entrega relación regla-artículo-edición-vigencia-responsable por contenido. CR02 es copia institucional, no auditoría de vigencia completa.

Completar registro y mapeo de manuales por clase antes de dar por cerrados contenidos legales.

H10 · El JSON no contiene todas las reglas curriculares

Claves raíz: documento, version, fecha, estado, nota, campos_solicitados, campos_adicionales, matriz, comparacion_THINK. Protocolos, CTM y prerrequisitos están en la narrativa.

Definir el paquete maestro con filas y reglas versionadas antes de usarlo como fuente para sistemas; no tratar el JSON actual como contrato completo.

El inventario adjunto identifica cada uno de los 50 códigos y qué desarrollo se localizó. “No localizado” se refiere a este conjunto de archivos, no a una afirmación sobre materiales que puedan existir fuera del espacio revisado. No se presenta un porcentaje global de proyecto terminado.

3. Correcciones redactadas

---

C01 · Retención del piloto (resuelve H04)

Sustituir la excepción de F02 del kit por: “En PIL-01, la doble valoración independiente cubre A, B y R, conforme al diseño del piloto. Si R tiene un solo observador, conservar la evidencia y registrar la toma incompleta para este procedimiento; no declarar cerrado el seguimiento del piloto. Completar una nueva observación con las condiciones previstas y dentro de la ventana aplicable”.

C02 · Identificadores de formularios (resuelve H05)

| Identificador completo | Significado |
| --- | --- |
| EDF-PC-01/F03@1.0.0 | Seguimiento individual del diseño general del piloto. |
| EDF-KIT-PIL01/F03@1.0.0 | Plantilla de Pasaporte Vial del kit PIL-01. |

Toda referencia debe incluir documento, formulario y versión. Aplicar la misma convención a F01/F02: el identificador corto sirve solo dentro de su documento. No fusionar ni renumerar silenciosamente registros históricos.

C03 · Señal de tarea, apoyo, pista e intervención (H03)

“Una señal prevista y declarada como estímulo del observable no es una pista. Si se evalúa frenar ante una señal, esa señal forma parte de la tarea. Una indicación añadida para resolver la decisión por el participante es pista. El apoyo de acceso facilita percibir o responder; la intervención de protección detiene la situación y se clasifica según su causa”. Incorporar esta distinción a las anclas de cada S.

C04 · Alinear SOS E1 S3 (H02)

Conservar “Cuidar el espacio común”. Añadir a la actividad un tramo de maqueta con paso obstruido por objetos: el participante identifica la obstrucción y pide al adulto despejarla, o recoge material propio situado en zona protegida. Evidencia: acción o petición observada. Criterio: protege el paso común sin entrar en calzada. Preparar anclas 0-3 y una variación nueva antes de evaluar.

C01-C02 corrigen derivados; C03-C04 necesitan integrarse en la edición curricular y sus rúbricas. Aquí quedan redactadas, no publicadas como una nueva versión de la matriz.

4. Cerrar la arquitectura conceptual

---

| Elemento | Definición propuesta para integrar |
| --- | --- |
| Unidad de evidencia | Un intento con fecha, procedencia, observables, versión, validez, apoyos/pistas, modalidad y vehículo. Puede ser válido aunque el protocolo de dominio aún esté incompleto. |
| Unidad de afirmación CTM | Persona + competencia y versión + rol/vehículo + modalidad y límites. Un resultado en auto o simulación no reemplaza el de moto o práctica. |
| Calidad C0-C3 | Aplicarla al respaldo de la afirmación curricular; registrar por separado validez y procedencia de cada evidencia. Precisar qué corroboración adicional justifica C3. |
| Prerrequisitos | Por destino, enumerar las S requeridas, evidencia equivalente admisible y quién resuelve equivalencias. No exigir cursos de edades previas a un adulto. |
| Paquete maestro | Un registro de edición con fichas, perfiles, rúbricas, protocolos, reglas CTM y fuentes normativas. PDF, Markdown y JSON son vistas sincronizadas de una versión. |

Secuencia de decisión propuesta

1. Determinar si el intento es interpretable. 2. Puntuar solo observables con oportunidad válida. 3. Formar el conjunto exigido por P1-P4 para un alcance definido. 4. Resolver discrepancias y contradicciones. 5. Determinar dominio y respaldo. 6. Publicar una afirmación limitada y su revisión prevista.

Precedencia de estados que falta formalizar

Para una afirmación ya demostrada: contradicción material abierta → en revisión; si no existe, revisión vencida o cambio pertinente → requiere actualización; si no existe, retención válida → sostenido; en otro caso → demostrado. Sin demostración previa: evidencia incompleta o brecha → en desarrollo; ausencia de evaluación → no evaluado. Conservar la resolución histórica en todos los casos.

Estas son propuestas documentales para resolver H06-H07. Deben traducirse a una tabla de transición con entradas, decisión, razón y salida; todavía no son comportamiento implementado ni autorización de publicación automática.

5. Ruta adulta sin conducción

---

La solución propuesta mantiene E4 CONDUZCO como etapa general, pero distingue el rol que se evalúa. No elimina S de conducción para declarar completa la ficha actual. Hay que revisar fichas/perfiles y versionar el criterio antes de acreditar esta ruta.

| Dominio | Alcance propuesto para adulto no conductor |
| --- | --- |
| PED | Elegir lugar y momento de cruce, comprobar aproximaciones y cambiar de plan. No exigir anticipar peatones desde el puesto de conducción. |
| CIC (si aplica) | Preparar bicicleta y resolver conflictos desde el rol ciclista. Protección del ciclista desde auto/moto pertenece a esos roles. |
| PAS | Verificar su protección, abordar/descender y responder a viaje inseguro como pasajero. No exigir iniciar o detener la marcha del vehículo. |
| SEN | Consultar e interpretar señales y prioridades pertinentes a su desplazamiento peatonal, ciclista o como pasajero. |
| RSK / DEC | Anticipar riesgos, esperar, cancelar o cambiar un trayecto desde su rol. Declarar el contexto de transferencia. |
| PRE | Preparar el desplazamiento, valorar apoyos y detectar condiciones de viaje inseguro. Inspección operativa del automóvil no es obligación universal. |
| COV / EME | Convivencia, comunicación y protección desde el rol real; mantener límites de intervención. |
| SOS | Comparar modos, accesibilidad e impacto y revisar el plan; conducción eficiente se reserva al rol conductor. |
| PRE-002 | Actualización de movilidad y apoyos durante la vida; una pausa sin conducir no obliga a retornar a conducción. |

Versionado necesario

Cambiar el sentido de una competencia o su acreditación es cambio mayor según la propia matriz. No presentar esta separación como una errata 1.0.1. La edición resultante debe declarar si crea desempeños por rol o sustituye criterios, y conservar la equivalencia y el historial de 1.0.0.

Esta página delimita el trabajo de redacción siguiente; no crea códigos nuevos ni da por resueltas sus rúbricas, evidencia o prerrequisitos.

6. Orden de cierre documental

---

| Orden | Trabajo | Condición de cierre |
| --- | --- | --- |
| 1 | Corregir perfiles, S y reglas comunes. | Resolver H01-H03; cada S debe tener objetivo, actividad, oportunidad observable, evidencia y anclas. |
| 2 | Precisar contratos de los cuatro componentes. | Resolver H06-H07 y H10: unidad de evidencia/afirmación, estado, alcance, revisión y fuente maestra. |
| 3 | Revisar localización normativa. | Registro de reglas, artículos, edición, fecha y responsable; completar mapeo de manuales por clase. |
| 4 | Reeditar derivados e inventariar pendientes. | Aplicar H04-H05 y versiones de origen. Desarrollar materiales con la arquitectura corregida. |
| 5 | Revisión documental integrada. | Trazabilidad completa del paquete que se declare terminado; ninguna contradicción de criterio sin resolver. |
| Luego | Ensayo entre adultos y piloto acotado. | Solo tras terminar y revisar el paquete que se vaya a usar y definir sede, fecha y equipo. |

Qué queda corregido en esta revisión

El estado de trabajo queda restituido a revisión documental en ESTADO_ACTUAL.md. Se separa lo que existe de lo que falta mediante un inventario de 50 códigos. Se redactan correcciones específicas y decisiones de arquitectura para integrar; no se presentan los materiales operativos anteriores como una fase ya habilitada.

Qué no se declara terminado

La reedición integrada de la matriz y derivados, las rúbricas y recursos faltantes, la verificación normativa completa y la adopción institucional permanecen pendientes. Las propuestas de este informe no sustituyen una nueva publicación sincronizada de la fuente curricular.

El siguiente trabajo concreto es resolver el perfil adulto por rol y revisar la alineación S-actividad-evidencia de las fichas, comenzando por H01-H03. No corresponde solicitar participantes ni marcar tareas de campo realizadas.

7. Verificaciones y referencias

---

Se revisaron la matriz y el piloto en Markdown/JSON, la guía del kit, el ensayo y la estructura del control de aplicación. Los anexos guardan huellas de los archivos examinados. No se ejecutaron ensayos con personas ni se evaluó software de EDUDRIVE.

Comparación THINK!

Se confirmaron los títulos de las 18 unidades de los tres mapas oficiales y su organización por 3-6, 7-12 y 13-16. Esto respalda la referencia comparativa; no valida los códigos, rúbricas, umbrales o protocolos propios de EDUDRIVE. No se detectó un error de conteo en las 18 unidades.

[Mapa THINK! 3-6](https://www.think.gov.uk/resource/curriculum-map-3-6/)

[Mapa THINK! 7-12](https://www.think.gov.uk/resource/curriculum-map-7-12/)

[Mapa THINK! 13-16](https://www.think.gov.uk/resource/curriculum-map-13-16/)

[MOPT: comunicado sobre pruebas por clase, 06/11/2025](https://www.mopt.go.cr/node/1230)

También se consultaron los PDF vinculados desde los tres mapas. El comunicado MOPT respalda que la matriz documente el anuncio de separación A/B; no sustituye comprobar los manuales completos ni la normativa actualmente aplicable. Consulta: 25/09/2026.

Referencias internas para reproducir hallazgos

Matriz v1.0.0: “Perfiles de salida”, “Competency Trust Model”, EDU-CIC-001.E1, EDU-SOS-001.E1 y fichas E4. Piloto v1.0.0: “Indicadores y decisión sobre el piloto” y F03. Kit PIL-01 v1.0.0: F02 y F03. Esas secciones permiten localizar H01-H07 sin depender de numeración de página de futuras ediciones.

Anexos de revisión

EDF_Inventario_Curricular_Revision01.json: cobertura por cada código. EDF_Hallazgos_Revision01.json: diez hallazgos, corrección, estado y fuentes. EDF_Revision_Curricular_01_v1.0.0.md: contenido editable de este informe y tabla de inventario. ESTADO_ACTUAL.md: punto de entrada al estado documental.

Resultado de control: estructura coherente en conteos e identificadores, con contradicciones y vacíos sustantivos pendientes. No se asigna “aprobado”, “listo para aplicar” ni porcentaje global de finalización.

# Anexo: inventario por código

| Código | Experiencias | Kit |
| --- | --- |
| EDU-PED-001.E1 | PIL-01 | PIL-01, borrador por corregir |
| EDU-CIC-001.E1 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-PAS-001.E1 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-SEN-001.E1 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-RSK-001.E1 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-DEC-001.E1 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-PRE-001.E1 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-CON-001.E1 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-COV-001.E1 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-EME-001.E1 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-SOS-001.E1 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-PED-001.E2 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-CIC-001.E2 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-PAS-001.E2 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-SEN-001.E2 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-RSK-001.E2 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-DEC-001.E2 | PIL-02 | No localizado en el corpus revisado |
| EDU-PRE-001.E2 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-CON-001.E2 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-COV-001.E2 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-EME-001.E2 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-SOS-001.E2 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-PED-001.E3 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-CIC-001.E3 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-PAS-001.E3 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-SEN-001.E3 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-RSK-001.E3 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-DEC-001.E3 | PIL-03 | No localizado en el corpus revisado |
| EDU-PRE-001.E3 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-CON-001.E3 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-COV-001.E3 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-EME-001.E3 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-SOS-001.E3 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-PED-001.E4 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-CIC-001.E4 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-PAS-001.E4 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-SEN-001.E4 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-RSK-001.E4 | PIL-04, PIL-05 | No localizado en el corpus revisado |
| EDU-DEC-001.E4 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-PRE-001.E4 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-CON-001.E4 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-COV-001.E4 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-EME-001.E4 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-SOS-001.E4 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-CON-002.E4 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-CON-003.E4 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-CON-004.E4 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-CON-005.E4 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-CON-006.E4 | Pendiente de desarrollo | No localizado en el corpus revisado |
| EDU-PRE-002.E4 | Pendiente de desarrollo | No localizado en el corpus revisado |