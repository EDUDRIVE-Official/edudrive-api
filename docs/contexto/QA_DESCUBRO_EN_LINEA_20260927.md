# DESCUBRO — Comprobación funcional en la app publicada

Fecha: 27 de septiembre de 2026.
Versión: `descubro-review-20260927r1`.
Destino: https://app.edudrive.vr506.com/descubro/cruzar-acompanado.

Se realizó una vuelta de revisión en la sesión de administración general, comenzando desde «Empezar juntos». Se informó previamente que quedaría registrada como práctica digital de revisión en esa cuenta. No corresponde a un participante ni a una evaluación pedagógica.

| Comprobación | Resultado observado |
| --- | --- |
| Inicio y explicaciones | Se recorrieron las tres explicaciones y se abrió la primera decisión. |
| Respuesta incorrecta | «Por la calzada» mostró «Hacemos una pausa» y la explicación correspondiente. |
| Recarga | La respuesta y su retroalimentación permanecieron después de recargar la página. |
| Reintento | Se volvieron a presentar las opciones y se pudo elegir «Por la acera». |
| Secuencia | Se completaron las cuatro decisiones con retroalimentación y la escena final de llegada. |
| Resultado | «Práctica digital completada» y S1–S3 como «Practicado en pantalla». |
| Pasaporte | Mostró el resumen interno, la habilidad en desarrollo y la observación en circuito protegido pendiente. El Pasaporte seguía sin emitirse. |
| Perfil | Mostró el mismo estado completado y el enlace «Ver mi práctica». |
| Regreso | El enlace del perfil recuperó el resultado completado, que se dejó abierto. |

La comprobación dejó una práctica completada, cuatro elecciones correctas y una elección incorrecta seguida de reintento en la cuenta administrativa. No se reinició ni eliminó ese registro. No se modificaron otras cuentas, código, migraciones ni configuración durante esta comprobación.

Se verificaron interacciones por teclado, recarga y navegación entre vistas. No se hizo una nueva prueba de cierre de sesión/dispositivo, ni una observación presencial. Las pruebas aisladas previas cubren persistencia y controles por rol; esta revisión complementa esas pruebas sobre la app publicada.

## Hallazgos de texto — resueltos en r2

1. En el Pasaporte no emitido, debajo de la práctica completada aparece «Tu recorrido está por comenzar». El texto debería referirse a la emisión pendiente del Pasaporte, para no contradecir el aprendizaje ya registrado.
2. La tarjeta repite «Práctica digital completada» en dos líneas consecutivas. Conviene mostrar el estado una sola vez, conservando el paso actual cuando haya una práctica en curso.

Estos hallazgos no impidieron guardar o recuperar la práctica. Se corrigieron posteriormente en `descubro-review-20260927r2`, publicado y comprobado en la sesión administrativa el mismo día. El Pasaporte muestra «Tu Pasaporte Vial está pendiente de emisión» y una sola línea de práctica completada; S1–S3 permanecen practicadas. El paso actual sigue disponible para prácticas en curso. No se inició otra práctica. Captura posterior: `PASAPORTE_textos_corregidos_20260927.png`.

Captura: `DESCUBRO_recorrido_verificado_20260927.png`.
