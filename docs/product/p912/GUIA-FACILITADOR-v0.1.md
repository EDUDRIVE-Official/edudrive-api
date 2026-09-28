# Instrumentos del piloto P912 — guía del facilitador

Versión 0.1. Estado: borrador para revisión pedagógica, vial y de accesibilidad. No está habilitado automáticamente en cursos publicados. Público acordado: primaria de 9–12 años, peatones y pasajeros.

## Materiales y separación de acceso

- `resources/curriculum/p912/instruments.v0.1.json`: banco interno con consignas y criterios por indicador. No enviarlo íntegro al navegador del estudiante: contiene los criterios reservados al evaluador.
- `CUADERNO-PARTICIPANTE-v0.1.md`: consignas sin criterios ni respuestas modelo. Entregar únicamente la forma correspondiente a la sesión.
- `REGISTRO-OBSERVACION-v0.1.md`: registro sin nombres ni datos de localización.
- `scripts/check-p912-instruments.mjs`: comprobaciones de integridad editorial, no de validez educativa.

## Aplicación de las formas

| Forma | Ítems | Momento | Ayuda de contenido | Devolución |
|---|---|---|---|---|
| Diagnóstico | D01–D04 | Antes de las demostraciones | No; sí apoyo de acceso neutral | Después de cerrar los cuatro ítems |
| Práctica | P01–P02 | Durante los encuentros 2–4 | Sí, registrar cada pista | Tras cada respuesta y reintento |
| Comprobación independiente | C01–C04 | Al finalizar la secuencia | No; sí apoyo de acceso neutral | Después de cerrar los cuatro ítems |
| Retención | R01–R02 | Propuesta: 3–4 semanas después | No; registrar exposición adicional al tema | Después de cerrar ambos ítems |

No corregir ni asentir como señal de acierto entre ítems independientes. No mostrar la escena animada que resuelve la decisión antes de recoger la respuesta. El orden queda fijo en esta primera versión; conservar IDs, no posiciones, para comparar registros. No hay opción B ni respuesta automática aprobatoria.

La duración no otorga puntos. Se puede pausar o dividir una sesión sin penalización; registrar la adaptación. No exigir lectura autónoma. La repetición literal, lectura neutral y comunicación aumentativa son apoyos de acceso, no pistas sobre el contenido.

## Qué observar

Los criterios del banco describen el razonamiento relevante, no una frase que deba memorizarse. Admitir equivalentes seguros. No exigir contacto visual con quien conduce ni asumir que escuchar basta. La responsabilidad de conductores y del entorno no se traslada al niño.

En cada indicador registrar dos dimensiones independientes:

1. **Resultado:** `observado` (el criterio aparece), `requiere_refuerzo` (respuesta explícita incompatible con el criterio), `informacion_insuficiente` (no se puede concluir), `no_aplicado` (no se presentó la tarea).
2. **Ayuda de contenido:** `ninguna`, `pregunta_orientadora`, `pista_directa`, `demostracion`. Los apoyos de acceso se registran aparte.

Así no confundimos «no respondió/no pudo acceder» con un error. Tampoco confundimos «lo logró con una pista» con «lo resolvió sin apoyo».

Si responde solo «espero» y el indicador pide explicar visibilidad, registrar información insuficiente para ese indicador; la misma respuesta puede aportar evidencia de pausa. Puede usarse una repregunta neutral: «Contame un poco más sobre tu decisión». Si se nombra el peligro que faltaba, ya es ayuda de contenido.

Cuando se necesite dar ayuda en diagnóstico o comprobación, hacerlo por bienestar/aprendizaje, registrar el cambio y clasificar la respuesta posterior como formativa. No convertirla en respuesta independiente. Si se interrumpe por malestar o inaccesibilidad, registrar la limitación, no un fracaso.

## Parejas para comparación

- D01/C01: espacio protegido y visibilidad.
- D02/C02: pausa ante conflicto y comprobación del giro.
- D03/C03: protección como pasajero y tránsito oculto.
- D04/C04: espacio protegido y solicitud de ayuda.

Son parejas propuestas con estructura similar, **no formas psicométricamente equivalentes demostradas**. No publicar porcentajes de eficacia a partir de ellas sin revisión y protocolo. Comparar únicamente indicadores observados en condiciones documentadas. R01/R02 muestrean los seis indicadores en solo dos contextos; no vuelven a medir toda su profundidad ni sirven para declarar retención total.

## Transferencia protegida — T01

Preparar una maqueta o plano de mesa diferente al de las prácticas: una acera continua, un vehículo que oculta un sector y una zona de espera. Puede usarse un patio cerrado sin vehículos activos. Confirmar el espacio antes de empezar; no realizar cruces reales.

Consigna: «Tu personaje está acompañado y necesita llegar al otro lado. Mostrá dónde espera, qué necesita saber y qué haría si no puede comprobarlo».

Registrar ESPACIO, VISIBILIDAD, PAUSA, COMPRUEBA y AYUDA con los mismos criterios del banco. No mover automáticamente el personaje por el estudiante. No tomar una observación de maqueta como prueba de conducta autónoma en tránsito.

## Transferencia protegida — T02

Usar sillas y una cinta como transporte y borde de acera, sin escalones ni estructuras que generen caída. La salida simulada da a una zona obstruida; no bloquear realmente vías de evacuación.

Consigna: «Todavía estás dentro. Mostrá qué harías antes de bajar y a quién lo comunicarías».

Registrar PASAJERO, ESPACIO y AYUDA. Una segunda versión con salida despejada permite comprobar que el criterio cambia con el entorno; no enseñar que siempre se debe permanecer dentro. Registrar si la segunda versión se presentó después de una explicación.

## Calibración del equipo antes de aplicar

Dos facilitadores revisan las mismas respuestas ficticias sin ver la clasificación del otro. Comparan por indicador y ayuda, explican desacuerdos y ajustan descriptores antes del piloto. Ejemplos:

- «No veo detrás de la furgoneta; me quedo en la acera lejos del borde»: ESPACIO y VISIBILIDAD observados si no hubo pista.
- «Voy a sacar la cabeza desde la calle para ver»: ESPACIO requiere refuerzo; no inferir automáticamente el resto.
- «Espero»: PAUSA puede estar observado; VISIBILIDAD necesita más información.
- «Me quedo» después de «¿no sería mejor quedarte?»: registrar ayuda directa; no independiente.
- Silencio tras una dificultad de lectura: información insuficiente y barrera de acceso, no error vial.

No usar estos ejemplos como entrenamiento previo del estudiante. Documentar quién revisó el instrumento, fecha, versión y cambios; no registrar revisión por el simple hecho de que un archivo existe.

## Devolución y decisiones

Entregar una síntesis descriptiva: qué reconoció, qué necesita practicar, con qué ayuda y en qué contexto. No usar «apto», «conductor seguro», porcentaje de dominio, ranking ni premio por rapidez.

El aprendizaje consistente requiere revisión de varias evidencias. Un resultado independiente aquí demuestra comprensión en esta situación, no una autorización para desplazarse solo. La práctica familiar es opcional; ofrecer T01/T02 en el centro cuando no sea posible acompañamiento en casa.

## Próxima integración técnica

El ensayo interno descrito en `ENSAYO-INTERNO-v0.1.md` ya implementa parte de este contrato y tiene pruebas dirigidas aprobadas. Solo admite datos ficticios y no está habilitado para estudiantes. La devolución de las formas independientes sigue siendo manual después del cierre; no hay calificación automática.

Crear un flujo independiente del botón de completar lección. Debe permitir cerrar diagnóstico con respuestas incorrectas o incompletas, guardar ayudas e intentos, ocultar criterios al estudiante y liberar devolución al cierre. El flujo actual de lecciones exige corregir para completar: **no usarlo para estas formas independientes**. No modificar certificados, reglas de desbloqueo o Pasaportes hasta definir y probar esa integración.
