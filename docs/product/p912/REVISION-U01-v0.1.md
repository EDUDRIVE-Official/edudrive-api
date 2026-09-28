# Revisión interna de P912-U01

Fecha: 2026-09-16. Revisor: asistente de desarrollo, no evaluador docente/vial externo. Alcance: guía textual U01 y flujo interno de ensayo; no las animaciones del catálogo completo. No constituye aprobación institucional ni certificación WCAG.

## Resultado técnico observado

| Comprobación | Resultado y alcance |
|---|---|
| Ancho 320 CSS px | Sin desbordamiento horizontal: ancho de documento 305, ventana 320. Captura inspeccionada; no equivale a prueba en dispositivo físico. |
| Ancho 1280 CSS px | Documento 1265, ventana 1280; sin desbordamiento horizontal. |
| Teclado | Ayuda abierta con Tab/Enter, foco visible; salto al contenido añadido y verificado con foco final en `pilot-main`. |
| Estructura | Un h1, un main, idioma es; controles desplegables nativos. Ayudas diferenciadas con U01-A/B/C. |
| Zoom 200% | No verificado: cinco pulsaciones del atajo no modificaron ancho ni pixel ratio del navegador integrado. No se considera aprobado por pasar reflujo. |
| Lector de pantalla | No ejecutado con NVDA/JAWS/VoiceOver. El árbol accesible se inspeccionó, pero no sustituye la experiencia auditiva real. |
| Interacción | Diagnóstico, práctica con pista/reintento y cierre independiente verificados previamente con datos ficticios; conservan historial y no exigen aciertos. |

Los cambios de tamaño se restauraron. No se cambiaron preferencias permanentes de tema ni se habilitó acceso a estudiantes.

## Revisión pedagógica interna

Se comprobó correspondencia entre cinco objetivos, actividades y criterios de los instrumentos mediante `check-p912-unit.mjs`, con casos negativos para detectar asociaciones falsas. La progresión distingue diagnóstico, enseñanza, transferencia, comprobación y seguimiento. Las variaciones impiden usar una posición fija como respuesta. Pedir ayuda no se trata como fracaso y los apoyos de acceso se distinguen de las pistas.

Límites que se mantienen: los ítems D/C son muy semejantes y no se ha demostrado equivalencia ni independencia respecto de memorización; el docente debe revisar dificultad y familiaridad. R01 no permite concluir retención de todos los objetivos. La forma informática presenta también ítems de pasajeros y aún no es una secuencia automatizada exclusiva de U01. No hay datos de comprensión ni carga docente de usuarios reales.

## Revisión vial interna

Se añadió al alcance una aclaración explícita: todas las decisiones de esperar ocurren antes de ingresar a la calzada, no en medio del cruce. La unidad no enseña por sí sola la ejecución completa del cruce. Mantiene acompañamiento adulto, no propone ensayos con tránsito activo ni culpabiliza al peatón por las obligaciones de los conductores. No se revisaron señales gráficas ni geometría de animaciones porque esta unidad es textual y de maqueta.

La referencia del MEP a entornos seguros y corresponsabilidad orienta el enfoque, pero no prueba alineación oficial por grado. La referencia NHTSA respalda considerar modelos y actividades guiadas como recursos pedagógicos, no acredita eficacia de EduDrive ni sustituye normas costarricenses.

## Fuentes de contraste consultadas

- MEP, [Camino Seguro](https://www.mep.go.cr/programas-proyectos/camino-seguro): entorno protegido y corresponsabilidad de la comunidad educativa. Pendiente mapeo oficial por grado.
- NHTSA, [Elementary-Age Child Pedestrian Training](https://www.nhtsa.gov/book/countermeasures-that-work/pedestrian-safety/countermeasures/other-strategies-behavior-change-1): recursos interactivos y modelos como complemento de enseñanza guiada. No importar reglas locales de transporte escolar estadounidense.
- W3C, [WCAG 2.2](https://www.w3.org/TR/WCAG22/) y [Resize Text](https://www.w3.org/WAI/WCAG22/Understanding/resize-text.html): referencia para pruebas de teclado, reflujo y ampliación. Esta revisión no abarca todos los criterios.

## Acta requerida para cierre externo

No se rellenan responsables ni aprobaciones ficticias. Completar por cada revisión:

| Campo | Docente de primaria | Especialista vial | Accesibilidad con lector real |
|---|---|---|---|
| Nombre y función | Pendiente | Pendiente | Pendiente |
| Fecha y versión revisada | Pendiente | Pendiente | Pendiente |
| Actividades/pruebas realizadas | Pendiente | Pendiente | Pendiente |
| Hallazgos y ajustes exigidos | Pendiente | Pendiente | Pendiente |
| Dictamen y limitaciones | Pendiente | Pendiente | Pendiente |

Docente: revisar lenguaje para 9–12, duración, neutralidad de preguntas, apoyos y valoración por indicador. Especialista vial: revisar espera, visibilidad, giros y límites del modelo, sin extrapolar a autorización autónoma. Accesibilidad: recorrer guía y formularios con lector real, comprobar nombres/estados/foco, ampliar realmente al 200% y documentar navegador y versión. Resolver hallazgos antes de declarar aprobación. No aplicar con menores hasta cerrar además protocolo de datos y autorizaciones.
