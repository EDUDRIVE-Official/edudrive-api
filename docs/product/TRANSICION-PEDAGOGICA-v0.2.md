# Transición pedagógica EduDrive — incremento 1

Fecha: 2026-09-16. Estado: en validación técnica y editorial.

## Decisión

Pausar la expansión de animaciones como principal medida de avance. Consolidar currículo, evidencia y revisión; validar primero peatones y pasajeros de primaria de 9–12 años. Ver PILOTO-PRIMARIA-9-12-v0.1.md.

## Contrato de este incremento

- La etapa vital no se deduce de historia, dilema, reflexión o desafío. Cuando no hay decisión editorial, las definiciones iniciales usan `pending_review`.
- Los valores anteriores siguen siendo legibles. No se ejecutan seeders, migraciones de datos ni cambios retroactivos de progreso.
- `pending_review` indica falta de clasificación, no un público universal ni aprobación curricular. El control actual de publicación no constituye homologación pedagógica; no usarlo como autorización del piloto.
- Las nuevas evidencias de lección completada incorporan `evidence_scope=formative_completion` y `demonstrates_mastery=false`. La ausencia de esas claves en registros históricos no significa dominio.
- No se cambia la puntuación, la emisión ni la validez histórica de certificados en este incremento. Sus reglas deberán revisarse en un cambio separado.
- El Pasaporte explica los límites de su puntuación sin afirmar diversidad a partir de cantidad.

## Próximos incrementos y condiciones de salida

1. Inventario de cursos persistidos, mapeo curricular explícito y revisión del piloto. Entregar propuesta de cambio por lección; aprobar antes de modificar versiones publicadas.
2. Instrumentos de práctica, diagnóstico y comprensión independiente. Registrar intentos, ayudas y contexto; no atribuir animación automática a conducta del estudiante. Revisar patrón de respuesta B y distractores.
3. Evidencia por indicador, práctica observada y retención. Evitar un puntaje global como sinónimo de dominio; distinguir observación familiar de evaluación especializada.
4. Revisión vial/visual/accesible y prueba con docentes y estudiantes. Documentar resultados antes de extender público o promesas de eficacia.

## Protección de compatibilidad

No volver a ejecutar los seeders para «actualizar» cursos existentes: varios reabren y reemplazan contenido. La migración editorial debe preservar identificadores, historial, versiones de evidencias y progresos, con vista previa y respaldo. Este incremento solo cambia las definiciones para futuras cargas y la clasificación de futuras evidencias.

## Validación pendiente

Pest ejecutado en Docker con SQLite en memoria: LessonLearningDesignTest, CompleteLessonHandlerTest y PilotInstrumentRunsTest suman 34 pruebas aprobadas (95 aserciones). Revisar además las pruebas de cursos piloto y publicación antes de una carga de contenido. Confirmar visualmente la etiqueta pendiente y el texto del Pasaporte. No hay despliegue ni validación con estudiantes en este incremento.
