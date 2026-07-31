# Diseño — Primeros componentes de UI (`ui/web`) en edudrive-api

## 1. Información del documento

| Campo | Valor |
|---|---|
| Fecha | 2026-07-31 |
| Proyecto | EDUDRIVE2026 |
| Componentes afectados | `edudrive-api` (implementación), `edudrive-design-system` (fuente de tokens, sin cambios de código) |
| Tipo | Diseño de nueva funcionalidad (brainstorming) |
| Estado | Aprobado por el usuario, pendiente de plan de implementación |

## 2. Contexto

El diseño original (`2026-07-29-consolidacion-autorizacion-organizaciones-design.md`) dejó planteado que el siguiente hito grande del ecosistema, después de tener funcionando los primeros endpoints de Organizaciones/Roles, sería arrancar el design system (`edudrive-design-system/ui/web`), hoy vacío salvo por los tokens (`tokens/*.json`).

Al explorar el contexto se encontró que **no existe una decisión de stack de frontend web tomada** para el panel administrativo/institucional. Los documentos de arquitectura vigentes (`ARC-002`, `ARC-004` en `edudrive-framework-auditado`) dejan explícitamente abiertas tres opciones (módulo Flutter, panel Laravel, SPA separada) y una serie de documentos desactualizados (`DEV-001/002/003`, mencionan React+TypeScript sobre un backend Java/Spring Boot inexistente) no reflejan la realidad del código actual. `edudrive-api` es un Laravel 12 + Tailwind 4 + Vite "de fábrica", sin ningún framework SPA instalado.

El usuario decidió resolver esto ahora, no aplazarlo: **Laravel Blade + Alpine.js + Tailwind**, aprovechando lo que `edudrive-api` ya tiene instalado, con los componentes viviendo directamente en ese repo (no en `edudrive-design-system/ui/web`, que se queda solo como fuente de los tokens JSON).

## 3. Objetivo

Construir los primeros 5 componentes de UI reutilizables (botón, input, card, badge, tabla) consumiendo los tokens de diseño ya definidos, con soporte de modo oscuro desde el inicio, y una página de demostración para verificarlos visualmente.

## 4. Arquitectura: pipeline de tokens → Tailwind

- `resources/css/app.css` gana un bloque `@theme` (sintaxis "CSS-first" de Tailwind 4) con los valores exactos de `edudrive-design-system/tokens/{colors,typography,spacing,radius,shadows}.json`: colores de marca y semánticos, familias tipográficas (Poppins/Inter/JetBrains Mono, reemplazando la fuente `Instrument Sans` del starter kit), escala de espaciado base 4px, radios y sombras (incluida la sombra `focus` verde para accesibilidad).
- Tailwind convierte automáticamente ese `@theme` en clases utilitarias (`bg-primary`, `text-primary`, `rounded-md`, etc.) y en variables CSS (`--color-primary`, ...), sin necesidad de `tailwind.config.js` adicional.
- Modo oscuro: `@custom-variant dark (&:where([data-theme="dark"], [data-theme="dark"] *));` para que el variant `dark:` de Tailwind responda al atributo `data-theme` en `<html>` en vez de a `prefers-color-scheme` únicamente. Un bloque de overrides define los valores dark reales de los tokens (fondo/superficie/texto oscuro), no una simple inversión de color.
- Alpine.js se instala vía `npm install alpinejs` y se registra en `resources/js/app.js`. Un `x-data` global (en el layout base) maneja el toggle: lee `localStorage`, cae a `prefers-color-scheme` si no hay preferencia guardada, y escribe `data-theme="light"|"dark"` en `<html>`.

## 5. Componentes

Todos como Blade components en `resources/views/components/ui/`, con slots donde da flexibilidad real (no solo props de array):

- **`<x-ui.button>`** — variantes `primary`/`secondary`/`danger`, tamaños `sm`/`md`/`lg`, soporta `disabled`, altura mínima 44px (touch target del token). Slot para el contenido (texto/icono).
- **`<x-ui.input>`** — prop `label`, `name`, `type`, `error` (string opcional); asocia `<label for>` con el `<input id>`; muestra el mensaje de error si existe con color semántico `danger`.
- **`<x-ui.card>`** — slot de contenido, padding = token `cardPadding` (16px), radio = token `card` (12px), sombra = token `card` (`sm`).
- **`<x-ui.badge>`** — variantes `success`/`info`/`warning`/`danger`; nunca depende solo del color (incluye texto, no un punto de color aislado), radio `full`.
- **`<x-ui.table>`** — slots `head` (fila de encabezados) y contenido (filas `<tr>`), bordes/espaciado de los tokens; `<th scope="col">` para accesibilidad.

## 6. Página de demostración

Ruta pública `GET /design-system` (sin autenticación, es una herramienta de desarrollo — se marcará explícitamente como tal; decidir si se restringe o se elimina antes de producción queda fuera de este alcance). Vista con un layout mínimo propio (no el layout de la app real, que no existe todavía) mostrando cada componente con sus variantes, más el botón de toggle de tema.

## 7. Verificación

- Un test Feature ligero: `GET /design-system` responde 200 y el HTML contiene el marcado esperado de cada componente (no un test exhaustivo de estilos, solo que la página renderiza).
- Verificación visual real en el navegador (claro y oscuro) antes de considerar el trabajo terminado, siguiendo la política del proyecto para cambios de UI.
- `composer quality` (Pint + PHPStan + suite completa) debe seguir en verde; este trabajo no debería tocar ningún módulo de dominio existente.

## 8. Fuera de alcance

- Cualquier decisión sobre si `/design-system` se protege o elimina antes de producción.
- Componentes adicionales más allá de los 5 listados (modal, dropdown, formularios complejos, etc.).
- Integrar estos componentes en una pantalla real de la aplicación (eso vendría después, cuando exista una necesidad concreta — por ejemplo, un panel de organizaciones).
- Tocar `edudrive-design-system` — los tokens se leen como fuente de verdad, no se modifican.
