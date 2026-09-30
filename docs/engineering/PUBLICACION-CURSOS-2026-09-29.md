# Publicación de los dos cursos completos

Confirmada el **29 de septiembre de 2026, 18:34:45 Costa Rica** (30 de septiembre, 00:34:45 UTC). El usuario confirmó en esta conversación la aprobación de ambos cursos completos. No se inspeccionó certificado externo ni se atribuyó aprobación a revisores registrados.

| Curso | Lecciones | Versión vigente | Versiones conservadas |
| --- | ---: | ---: | --- |
| EDU-EXP-001 Camino Seguro | 27 | 2 | 1 y 2 |
| EDU-EXP-003 Pasajero Responsable | 12 | 2 | 1 y 2 |

Se integraron textos y apoyos ampliados en las cinco lecciones trabajadas: 20 bloques (12 de Camino Seguro y 8 de Pasajero Responsable). Las otras 34 conservan su contenido. Las 39 fichas dejan `pending_review`: 38 en `discover` (9–12 acompañado) y PR04 en `teach`, por la responsabilidad adulta sobre la selección de protección descrita en la revisión editorial. Se mantienen indicadores, reglas de evidencia y fuentes con sus fechas; no se inventó una revisión normativa nueva. Publicar no demuestra eficacia pedagógica ni sustituye un piloto.

Se utilizó el ciclo normal de revisión/aprobación/publicación y su control de calidad, en una transacción para ambos cursos. Se conservaron todos los IDs. Huellas de matrículas, finalizaciones y progreso DESCUBRO idénticas antes/después. Dos eventos de auditoría `academic.editorial_revision.published` registran aprobación declarada y huella del plan. Las dos vistas previas integradas respondieron 200 antes del commit. La verificación posterior en solo lectura confirmó 39 lecciones, versiones 1 y 2 y cero fichas pendientes.

Se retiró «Probar borrador» del catálogo, comprobado mediante respuesta autenticada del kernel HTTP (200). La antigua ruta administrativa `pilot-instruments/editorial-preview` permanece como referencia editorial; el acceso publicado es https://app.edudrive.vr506.com/courses. El navegador remitió al login por falta de sesión activa; no se completó una revisión visual autenticada en esta entrega.

## Entrega y evidencias

- Versión técnica activa: `editorial-approved-20260929r1`, app/worker/scheduler/nginx.
- App: `sha256:3398e960e160f19824a82673012041e47a7af8cbec57c380612e9d916b3dcb92`.
- Nginx: `sha256:af48146799d4fff4a845f706e57b0c7e6dea6eacf5b0f3b984c9d747b0d5283d`.
- Entrega: `/opt/edudrive/releases/editorial-approved-20260929r1`.
- Dump previo restaurado íntegramente en PostgreSQL aislado: `before.dump`, SHA256 `c9a17f440f3f540efd23cddc98277323e0efea9e0a057bc645a3ef6acf22b019`.
- Paquete incremental: SHA256 `cdad3b4e52031e5df707295e6cb43f97e0baebb5b26f4d973c5d61a18fb740fc`.
- Resultados: `validation.json`, `publication.json`, `activated-at.txt` en la entrega.
- Sin migraciones, seeders, cambios de credenciales ni permisos de usuarios.

Antes de escribir datos se corrigieron permisos de lectura de `docs`/`scripts` en la imagen y se repitió el ensayo con el usuario real del contenedor. Tras recrear la app, el proxy conservó la dirección anterior y produjo 502; se recuperó recreando conjuntamente app y nginx. También hubo 404 transitorios. Al cierre `/up` y `/login` respondían 200 y nginx estaba saludable.

La reversión automática intentada no encontró las imágenes `consolidacion-20260929r1` localmente ni en un registro público. No se determinó la causa de su ausencia. La recuperación usó las imágenes candidatas ya validadas. **No confiar en el override anterior como reversión ejecutable sin reconstruir/verificar sus imágenes.**

## Pruebas y recuperación

Integración: 192 pruebas focalizadas (825 aserciones), PHPStan de los dos bloques de dominio sin errores y formato validado. Publicación completa ensayada sobre respaldo restaurado con rollback, luego aplicada a producción. No se repitió aquí la suite general de 2605 pruebas.

`scripts/publish-editorial-revision.php` conserva IDs y compara la fuente completa antes de modificar. No repetirlo después de esta publicación: debe rechazar el contenido ya actualizado. El plan JSON pendiente se conserva como evidencia histórica de preparación, no como estado actual.

Antes de recuperar, evaluar escrituras posteriores. Las versiones 1 y el dump previo permanecen disponibles. No restaurar indiscriminadamente el dump ni regresar al código antiguo mientras haya campos `supplement`. Código y documentos todavía sin commit/push: se desplegó desde el árbol de trabajo y la fuente de la entrega anterior.
