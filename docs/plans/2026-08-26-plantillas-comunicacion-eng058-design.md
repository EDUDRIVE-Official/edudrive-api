# ENG-058 — Plantillas de comunicación: alcance acordado

Tercera y última historia de la Fase 11 — Comunicación y notificaciones. Extiende `Modules\Notification` con un tercer agregado, `CommunicationTemplate` — un catálogo versionado de plantillas de contenido con sustitución de variables, independiente del envío de notificaciones (ENG-056/057).

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Integración con el envío**: capacidad independiente. Solo el catálogo de plantillas y su renderizado (sustituir variables, vista previa) — `SendNotificationCommand` no se modifica para aceptar una plantilla; sigue recibiendo `subject`/`body` libres. Mismo criterio que ENG-056/057, que también diferieron la integración automática entre módulos.
2. **Idiomas**: una fila por combinación código+idioma (ej. `welcome-email`+`es`, `welcome-email`+`en`), cada una con su propio ciclo de versión independiente. El código es único por idioma, no globalmente único.
3. **Marca institucional**: convención de variables reservadas (ej. `{{institution_name}}`, `{{institution_logo_url}}`) que el llamador provee al renderizar, igual que cualquier otra variable declarada — sin plantillas específicas por organización ni resolución con fallback.
4. **Validación de variables**: la plantilla declara una lista cerrada de nombres de variables esperadas. Renderizar sin proveer todas las declaradas lanza un error (`MissingTemplateVariable`, 422). Placeholders no declarados en el texto simplemente no se sustituyen (quedan como texto literal).

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Versionado simple, sin snapshots históricos**: mismo criterio que `Badge::updateContent()` (ENG-052) — `CommunicationTemplate::updateContent()` incrementa un campo `version` (entero) sin conservar el contenido anterior. Edición bloqueada si la plantilla está `retired` (`InvalidCommunicationTemplateTransition`, 422, reutilizada también para "retirar dos veces").
- **Placeholders con sintaxis `{{variable}}`**: sustitución literal por `str_replace`, sin motor de plantillas (no Blade/Twig) — mismo espíritu minimalista que el resto de los campos de texto libre en la sesión.
- **`templates.view` no se otorga a `Student`**: a diferencia de `achievements.view`/`badges.view`/`challenges.view` (catálogos de navegación abierta y motivacional), las plantillas de comunicación son una herramienta interna administrativa/docente, mismo criterio que `road_passports.view`/`certifications.view`/`simulators.view` (sin acceso de Student).
- **Vista previa bajo `templates.view`, no `templates.manage`**: previsualizar es una acción de solo lectura (renderiza sin persistir), no requiere el permiso de gestión.
- **Unicidad por código+idioma**: `findByCodeAndLocale()` en el repositorio, en vez de `findByCode()` como en `Achievement`/`Badge` — el mismo código puede repetirse en distintos idiomas.

## Incluye (del roadmap)

- Plantillas versionadas.
- Variables.
- Idiomas.
- Marca institucional.
- Vista previa.

## Diferido explícitamente

- Integración con `SendNotificationCommand` (uso de una plantilla al enviar una notificación real).
- Plantillas específicas por organización con resolución en cascada.
- Motor de plantillas real (condicionales, bucles, herencia) — solo sustitución literal de variables.
- Historial completo de versiones anteriores (solo se conserva el número de versión).
