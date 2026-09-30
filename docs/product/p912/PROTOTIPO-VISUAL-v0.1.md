# Prototipo visual: Luna y el giro

Ruta interna: `/pilot-instruments/visual`. Se conserva separado del recorrido de nueve preguntas, sin calificar ni guardar decisiones. Solo local/testing y permiso de gestión. Es práctica, no diagnóstico: las explicaciones están presentes en el HTML y se ocultan hasta elegir.

Vista 3D estilizada: dos carriles por calle (uno por sentido), paso en el brazo sur fuera del centro, Luna y acompañante en la acera, señal peatonal favorable y carro con direccional derecha. La escena inicial no avanza automáticamente. Elección breve, luego desenlace y pregunta oral de explicación. Incluye narración opcional del navegador, descripción textual, pausa, repetición y desenlace sin movimiento. Respeta preferencia de movimiento reducido detectada al abrir. No hay reproducción de voz automática.

Al esperar: carro desacelera y se detiene antes de la línea; después ambos personajes cruzan. Se representa un ejemplo con los demás movimientos libres, no una regla de cruzar apenas se detenga un único vehículo. Al avanzar por el verde: ambos empiezan a cruzar y toda la escena se congela a los 2,5 segundos antes de llegar al carril del carro, aún durante el giro. No hay contacto. El mensaje diferencia esta pausa didáctica de detenerse físicamente a mitad del cruce; no se presenta al carro congelado como si ya hubiera cedido el paso.

## Comprobaciones

- Compilación y prueba HTTP de autorización/renderizado aprobadas (1 prueba, 6 aserciones).
- Captura del estado inicial inspeccionada: escena y controles visibles. Ambos desenlaces comprobados en navegador, así como pausa, repetición y final sin movimiento. Narración audible no comprobada.
- `scripts/check-pilot-turn.mjs` usa la misma trayectoria que el renderizador. Revisa la huella rectangular del carro sobre la calzada y antes de la línea de detención, inmovilidad del vehículo antes del cruce en la elección de esperar y entrada real de ambos peatones al paso en la elección anticipada, con congelación de posiciones antes del carril del vehículo. Detectó un margen insuficiente y se adelantó la detención. No es un modelo de dinámica vehicular ni aprobación vial externa.
- Las ruedas permanecen fijas respecto del coche; no hay oscilación lateral artificial. La figura/edificios son formas estilizadas, no modelos fotorrealistas.

Pendiente: devolución del responsable sobre claridad, escala y utilidad; revisión docente/vial, audio real, lectores de pantalla y pruebas de dispositivos. No multiplicar la escena por todo el curso hasta evaluar este ejemplo.
