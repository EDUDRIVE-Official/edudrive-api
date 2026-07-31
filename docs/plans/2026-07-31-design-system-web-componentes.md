# Primeros componentes de UI (`ui/web`) — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Construir los primeros 5 componentes Blade reutilizables (botón, input, card, badge, tabla) para el panel web de EDUDRIVE, consumiendo los tokens de `edudrive-design-system` vía Tailwind 4, con modo oscuro manual desde el inicio y una página de demostración verificable en el navegador.

**Architecture:** Los tokens (`edudrive-design-system/tokens/*.json`) se transcriben una sola vez a un bloque `@theme` de Tailwind 4 en `resources/css/app.css` (colores, tipografía, radios, sombras); Tailwind convierte eso en clases utilitarias y variables CSS automáticamente. El modo oscuro usa un atributo `data-theme` en `<html>`, controlado por un script inline (evita parpadeo) + un botón con Alpine.js que persiste la preferencia en `localStorage`. Los componentes son Blade components anónimos (sin clases PHP) en `resources/views/components/ui/`.

**Tech Stack:** Laravel 12, Blade components, Tailwind CSS 4 (sintaxis CSS-first `@theme`), Alpine.js 3, Pest.

---

## Convenciones usadas en este plan (leer una vez)

- Todos los comandos se ejecutan vía Docker, no con PHP/npm locales del host:
  ```bash
  MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-design-system <comando>
  ```
  (La imagen `edudrive-worktree-design-system` ya existe, construida desde `docker/php/Dockerfile`.)
- Para `npm install`/`npm run build` se necesita Node, que NO está en la imagen PHP. Usa la imagen oficial de Node en su lugar:
  ```bash
  MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html node:22-slim npm install
  MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html node:22-slim npm run build
  ```
- Los tokens fuente de verdad están en `C:\Users\vr506\Documents\EDUDRIVE2026\edudrive-design-system\tokens\*.json` — no se modifican, solo se leen. Los valores exactos ya están transcritos en el código de este plan; no hace falta volver a leer los JSON.
- Después de **cada** tarea: correr `composer quality` (Pint + PHPStan + suite completa) y confirmar verde antes de comitear.
- Este trabajo es puramente de presentación (Blade/CSS/JS) — no toca ningún módulo de dominio (`modules/Academic`, `modules/Organization`, `modules/Authorization`, etc.). Si `composer quality` reporta un fallo en algo de `modules/`, es una señal de que algo salió mal (posible colisión de nombres, error de sintaxis que rompe el autoload), no de que ese módulo necesitaba cambios.

---

### Task 1: Tokens → Tailwind (`@theme`, tipografía, modo oscuro)

**Files:**
- Modify: `resources/css/app.css`

No hay test automatizado para este paso (es CSS puro, sin lógica). La verificación es visual, en la Tarea 9.

**Step 1: Reemplazar el contenido completo de `resources/css/app.css`**

Contenido actual (a reemplazar):
```css
@import 'tailwindcss';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';
@source '../**/*.js';

@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
        'Segoe UI Symbol', 'Noto Color Emoji';
}
```

Nuevo contenido completo:
```css
@import 'tailwindcss';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';
@source '../**/*.js';

/*
 * Habilita el variant `dark:` de Tailwind para responder al atributo
 * data-theme="dark" en <html> (control manual vía Alpine.js), en vez de
 * depender únicamente de prefers-color-scheme.
 */
@custom-variant dark (&:where([data-theme="dark"], [data-theme="dark"] *));

@theme {
    /* Tipografía — edudrive-design-system/tokens/typography.json */
    --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
        'Segoe UI Symbol', 'Noto Color Emoji';
    --font-heading: 'Poppins', ui-sans-serif, system-ui, sans-serif;
    --font-mono: 'JetBrains Mono', ui-monospace, 'Consolas', monospace;

    /* Espaciado — edudrive-design-system/tokens/spacing.json (baseUnit 4px) */
    --spacing: 0.25rem;

    /* Radios — edudrive-design-system/tokens/radius.json */
    --radius-xs: 4px;
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 24px;
    --radius-full: 9999px;

    /* Sombras — edudrive-design-system/tokens/shadows.json */
    --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.08);
    --shadow-sm: 0 2px 6px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 6px 16px rgba(0, 0, 0, 0.12);
    --shadow-lg: 0 12px 30px rgba(0, 0, 0, 0.16);
    --shadow-focus: 0 0 0 3px rgba(139, 195, 74, 0.35);

    /* Colores de marca — edudrive-design-system/tokens/colors.json */
    --color-primary: #125221;
    --color-secondary: #2e7d32;
    --color-accent: #8bc34a;
    --color-safety: #fbc02d;

    /* Colores semánticos */
    --color-success: #2e7d32;
    --color-info: #1976d2;
    --color-warning: #f9a825;
    --color-danger: #c62828;

    /* Colores neutrales — valores claros (modo oscuro se sobreescribe abajo) */
    --color-background: #f8f9fa;
    --color-surface: #ffffff;
    --color-text: #222222;
    --color-text-secondary: #5f6368;
    --color-border: #dadce0;
}

/*
 * Modo oscuro — edudrive-design-system/tokens/colors.json (neutral.*Dark).
 * text-secondary y border no tienen variante oscura definida todavía en el
 * design system; se mantienen iguales hasta que se agregue esa decisión ahí.
 */
[data-theme='dark'] {
    --color-background: #1b1f23;
    --color-surface: #252a30;
    --color-text: #f5f7f9;
}
```

**Step 2: Verificar que el build de Vite no falle**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html node:22-slim npm install
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html node:22-slim npm run build
```
Expected: ambos comandos terminan sin error (el segundo genera `public/build/`).

**Step 3: Correr `composer quality` para confirmar que nada de PHP se rompió**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-design-system composer quality
```
Expected: PASS (este cambio no toca PHP, así que debe seguir exactamente igual que la línea base).

**Step 4: Commit**

```bash
git add resources/css/app.css
git commit -m "feat(design-system): wire design tokens into Tailwind theme"
```

---

### Task 2: Alpine.js + bootstrap de tema + layout base

**Files:**
- Modify: `package.json`
- Modify: `resources/js/app.js`
- Create: `resources/views/components/layouts/design-system.blade.php`

**Step 1: Agregar Alpine.js como dependencia**

Modificar `package.json` (contenido completo):
```json
{
    "$schema": "https://www.schemastore.org/package.json",
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite"
    },
    "dependencies": {
        "alpinejs": "^3.14.9"
    },
    "devDependencies": {
        "@tailwindcss/vite": "^4.0.0",
        "axios": "^1.11.0",
        "concurrently": "^9.0.1",
        "laravel-vite-plugin": "^2.0.0",
        "tailwindcss": "^4.0.0",
        "vite": "^7.0.7"
    }
}
```

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html node:22-slim npm install
```
Expected: instala `alpinejs` y actualiza `package-lock.json`.

**Step 2: Registrar Alpine en `resources/js/app.js`**

Contenido completo nuevo:
```js
import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
```

**Step 3: Crear el layout base con bootstrap de tema (sin parpadeo) y el toggle**

Crear `resources/views/components/layouts/design-system.blade.php`:
```blade
@props(['title' => 'EDUDRIVE — Design System'])
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <script>
        (function () {
            var stored = localStorage.getItem('edudrive-theme');
            var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background font-sans text-text">
    <div class="mx-auto max-w-4xl px-6 py-8">
        <div class="mb-8 flex items-center justify-between">
            <h1 class="font-heading text-2xl font-bold">{{ $title }}</h1>
            <button
                type="button"
                x-data
                @click="
                    var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-theme', next);
                    localStorage.setItem('edudrive-theme', next);
                "
                class="rounded-sm border border-border px-3 py-2 text-sm text-text hover:bg-surface focus-visible:outline-none focus-visible:shadow-focus"
            >
                Cambiar tema
            </button>
        </div>
        {{ $slot }}
    </div>
</body>
</html>
```

Nota: el script inline del `<head>` corre antes de que Tailwind/Alpine carguen, evitando el parpadeo de tema incorrecto al recargar la página. El botón no necesita estado de Alpine (`x-data` vacío) porque solo lee/escribe el atributo del DOM directamente — no hace falta un store más elaborado para un toggle tan simple.

**Step 4: Verificar `composer quality`**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-design-system composer quality
```
Expected: PASS.

**Step 5: Commit**

```bash
git add package.json package-lock.json resources/js/app.js resources/views/components/layouts/design-system.blade.php
git commit -m "feat(design-system): add Alpine.js and base layout with theme toggle"
```

---

### Task 3: Componente `<x-ui.button>`

**Files:**
- Create: `resources/views/components/ui/button.blade.php`

**Step 1: Implementar**

```blade
@props(['variant' => 'primary', 'size' => 'md', 'disabled' => false])
@php
    $variants = [
        'primary' => 'bg-primary text-white hover:bg-secondary',
        'secondary' => 'border border-border bg-surface text-primary hover:bg-background',
        'danger' => 'bg-danger text-white hover:bg-danger/90',
    ];

    $sizes = [
        'sm' => 'min-h-[44px] px-3 text-sm',
        'md' => 'min-h-[44px] px-4 text-base',
        'lg' => 'min-h-[44px] px-6 text-lg',
    ];

    $classes = 'inline-flex items-center justify-center gap-2 rounded-sm font-sans font-medium transition '
        . 'focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50 '
        . ($variants[$variant] ?? $variants['primary']) . ' '
        . ($sizes[$size] ?? $sizes['md']);
@endphp
<button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }} @disabled($disabled)>
    {{ $slot }}
</button>
```

Nota sobre altura táctil: los 3 tamaños usan `min-h-[44px]` (el mínimo de 44px que exige el token `touchTarget` de `breakpoints.json`), variando solo el padding horizontal y el tamaño de texto.

**Step 2: Verificar `composer quality`** (sin test automatizado propio — se verifica junto con la página de demo en la Tarea 8, y visualmente en la Tarea 9)

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-design-system composer quality
```
Expected: PASS.

**Step 3: Commit**

```bash
git add resources/views/components/ui/button.blade.php
git commit -m "feat(design-system): add ui.button component"
```

---

### Task 4: Componente `<x-ui.input>`

**Files:**
- Create: `resources/views/components/ui/input.blade.php`

**Step 1: Implementar**

```blade
@props(['name', 'label' => null, 'type' => 'text', 'error' => null])
@php
    $id = $attributes->get('id', $name);
@endphp
<div class="flex flex-col gap-1">
    @if ($label)
        <label for="{{ $id }}" class="font-sans text-sm font-medium text-text">{{ $label }}</label>
    @endif

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        {{ $attributes->except(['id', 'name', 'type'])->merge([
            'class' => 'min-h-[44px] rounded-sm border bg-surface px-3 font-sans text-base text-text '
                . 'focus-visible:outline-none focus-visible:shadow-focus '
                . ($error ? 'border-danger' : 'border-border'),
        ]) }}
    />

    @if ($error)
        <p class="font-sans text-sm text-danger">{{ $error }}</p>
    @endif
</div>
```

**Step 2: Verificar `composer quality`**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-design-system composer quality
```
Expected: PASS.

**Step 3: Commit**

```bash
git add resources/views/components/ui/input.blade.php
git commit -m "feat(design-system): add ui.input component"
```

---

### Task 5: Componente `<x-ui.card>`

**Files:**
- Create: `resources/views/components/ui/card.blade.php`

**Step 1: Implementar**

```blade
@props([])
<div {{ $attributes->merge(['class' => 'rounded-md bg-surface p-4 shadow-sm']) }}>
    {{ $slot }}
</div>
```

**Step 2: Verificar `composer quality`**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-design-system composer quality
```
Expected: PASS.

**Step 3: Commit**

```bash
git add resources/views/components/ui/card.blade.php
git commit -m "feat(design-system): add ui.card component"
```

---

### Task 6: Componente `<x-ui.badge>`

**Files:**
- Create: `resources/views/components/ui/badge.blade.php`

**Step 1: Implementar**

```blade
@props(['variant' => 'info'])
@php
    $variants = [
        'success' => 'bg-success/10 text-success',
        'info' => 'bg-info/10 text-info',
        'warning' => 'bg-warning/10 text-warning',
        'danger' => 'bg-danger/10 text-danger',
    ];
@endphp
<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1 rounded-full px-3 py-1 font-sans text-sm font-medium '
        . ($variants[$variant] ?? $variants['info']),
]) }}>
    {{ $slot }}
</span>
```

Nota de accesibilidad: el badge siempre lleva texto dentro del slot (nunca es solo un punto de color) — quien lo use debe pasar una etiqueta legible (ej. "Activa", "Pendiente"), no depender solo del color para transmitir el estado. Esto no se puede forzar desde el componente mismo; queda como convención de uso, documentada aquí y en la página de demo.

**Step 2: Verificar `composer quality`**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-design-system composer quality
```
Expected: PASS.

**Step 3: Commit**

```bash
git add resources/views/components/ui/badge.blade.php
git commit -m "feat(design-system): add ui.badge component"
```

---

### Task 7: Componente `<x-ui.table>`

**Files:**
- Create: `resources/views/components/ui/table.blade.php`

**Step 1: Implementar**

```blade
@props([])
<div class="overflow-x-auto rounded-md border border-border">
    <table {{ $attributes->merge(['class' => 'w-full text-left font-sans text-sm']) }}>
        @isset($head)
            <thead class="bg-background text-text-secondary">
                {{ $head }}
            </thead>
        @endisset
        <tbody class="divide-y divide-border text-text">
            {{ $slot }}
        </tbody>
    </table>
</div>
```

Uso esperado (ver Tarea 8 para el ejemplo completo):
```blade
<x-ui.table>
    <x-slot:head>
        <tr>
            <th scope="col" class="px-4 py-2">Columna</th>
        </tr>
    </x-slot:head>
    <tr>
        <td class="px-4 py-2">Valor</td>
    </tr>
</x-ui.table>
```

**Step 2: Verificar `composer quality`**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-design-system composer quality
```
Expected: PASS.

**Step 3: Commit**

```bash
git add resources/views/components/ui/table.blade.php
git commit -m "feat(design-system): add ui.table component"
```

---

### Task 8: Página de demostración (`/design-system`) — con test

**Files:**
- Create: `resources/views/design-system.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/DesignSystemTest.php`

**Step 1: Escribir el test que debe fallar**

```php
<?php

declare(strict_types=1);

use Tests\TestCase;

it('muestra la página de demostración del design system', function (): void {
    /** @var TestCase $this */
    $response = $this->get('/design-system');

    $response->assertOk();
    $response->assertSeeText('Botones');
    $response->assertSeeText('Cambiar tema');
});
```

**Step 2: Correr el test y confirmar que falla**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-design-system php artisan test tests/Feature/DesignSystemTest.php
```
Expected: FAIL (404, la ruta no existe todavía).

**Step 3: Implementar la ruta y la vista**

Modificar `routes/web.php` (contenido completo):
```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/design-system', function () {
    return view('design-system');
})->name('design-system');
```

Crear `resources/views/design-system.blade.php`:
```blade
<x-layouts.design-system title="EDUDRIVE — Design System">
    <div class="flex flex-col gap-10">
        <section>
            <h2 class="mb-4 font-heading text-xl font-semibold">Botones</h2>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button variant="primary">Primario</x-ui.button>
                <x-ui.button variant="secondary">Secundario</x-ui.button>
                <x-ui.button variant="danger">Peligro</x-ui.button>
                <x-ui.button variant="primary" size="sm">Chico</x-ui.button>
                <x-ui.button variant="primary" size="lg">Grande</x-ui.button>
                <x-ui.button variant="primary" :disabled="true">Deshabilitado</x-ui.button>
            </div>
        </section>

        <section>
            <h2 class="mb-4 font-heading text-xl font-semibold">Inputs</h2>
            <div class="flex max-w-sm flex-col gap-4">
                <x-ui.input name="nombre" label="Nombre" placeholder="Escuela de Manejo EDUDRIVE" />
                <x-ui.input name="correo" label="Correo" type="email" error="Este campo es obligatorio." />
            </div>
        </section>

        <section>
            <h2 class="mb-4 font-heading text-xl font-semibold">Cards</h2>
            <x-ui.card class="max-w-sm">
                <p class="font-sans text-text">Contenido de ejemplo dentro de una card.</p>
            </x-ui.card>
        </section>

        <section>
            <h2 class="mb-4 font-heading text-xl font-semibold">Badges</h2>
            <div class="flex flex-wrap gap-2">
                <x-ui.badge variant="success">Activa</x-ui.badge>
                <x-ui.badge variant="info">Info</x-ui.badge>
                <x-ui.badge variant="warning">Pendiente</x-ui.badge>
                <x-ui.badge variant="danger">Inactiva</x-ui.badge>
            </div>
        </section>

        <section>
            <h2 class="mb-4 font-heading text-xl font-semibold">Tabla</h2>
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-4 py-2">Organización</th>
                        <th scope="col" class="px-4 py-2">Tipo</th>
                        <th scope="col" class="px-4 py-2">Estado</th>
                    </tr>
                </x-slot:head>
                <tr>
                    <td class="px-4 py-2">Escuela de Manejo EDUDRIVE</td>
                    <td class="px-4 py-2">Escuela de manejo</td>
                    <td class="px-4 py-2"><x-ui.badge variant="success">Activa</x-ui.badge></td>
                </tr>
            </x-ui.table>
        </section>
    </div>
</x-layouts.design-system>
```

**Step 4: Correr el test y confirmar que pasa**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-design-system php artisan test tests/Feature/DesignSystemTest.php
```
Expected: PASS.

**Step 5: Correr `composer quality` completo**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-design-system composer quality
```
Expected: PASS.

**Step 6: Commit**

```bash
git add resources/views/design-system.blade.php routes/web.php tests/Feature/DesignSystemTest.php
git commit -m "feat(design-system): add /design-system demo page"
```

---

### Task 9: Verificación visual final (navegador) y build de producción

**Files:** ninguno (solo verificación)

**Step 1: Levantar el servidor de desarrollo**

Este proyecto usa Docker + Vite; para la verificación visual, usar las herramientas de preview/browser del entorno (Browser pane) apuntando al servidor de desarrollo Laravel (`php artisan serve` o el contenedor `app` si ya está corriendo con nginx) más `npm run dev` para Vite con HMR, o simplemente `npm run build` y servir los assets compilados. Cualquiera de las dos formas es válida — el objetivo es cargar `/design-system` en un navegador real.

**Step 2: Verificar en modo claro**

- Cargar `http://localhost:<puerto>/design-system`.
- Confirmar visualmente: los 3 botones (primario verde oscuro `#125221`, secundario con borde, peligro rojo), los 2 inputs (uno normal, uno con mensaje de error en rojo), la card con sombra sutil, los 4 badges con colores semánticos correctos, la tabla con encabezado gris claro y una fila.
- Confirmar que el foco visible (Tab hasta un botón/input) muestra el anillo verde (`shadow-focus`).

**Step 3: Verificar en modo oscuro**

- Hacer clic en "Cambiar tema".
- Confirmar que el fondo pasa a `#1B1F23`, las cards/inputs a `#252A30`, el texto a `#F5F7F9`, y que los botones/badges siguen siendo legibles (mismo colores semánticos, que ya tienen buen contraste sobre superficies oscuras al usar `/10` de opacidad para los fondos de badge).
- Recargar la página (F5) y confirmar que el tema oscuro persiste (sin parpadeo al fondo claro antes de aplicarse).

**Step 4: Si algo se ve mal**

Volver al archivo correspondiente (`app.css` para colores/tokens, el componente Blade específico para estructura/spacing), corregir, volver a Step 2.

**Step 5: Confirmar `composer quality` una vez más como cierre**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-design-system composer quality
```
Expected: PASS.

No hay commit en esta tarea salvo que Step 4 haya requerido una corrección — en ese caso, commitear la corrección con un mensaje descriptivo (ej. `fix(design-system): correct badge contrast in dark mode`).

---

## Fuera de alcance (no hacer en este plan)

- Proteger o eliminar la ruta `/design-system` antes de producción — pendiente, fuera de este alcance.
- Componentes adicionales (modal, dropdown, select, checkbox/radio, etc.).
- Integrar estos componentes en una pantalla real de la aplicación.
- Remapear la escala tipográfica completa de Tailwind (`text-xs`...`text-display`) o los "roles" tipográficos (`heading1`, `body`, etc.) — solo se conectaron las familias de fuente (`font-sans`, `font-heading`, `font-mono`); el resto de la escala usa los tamaños por defecto de Tailwind, que ya son razonablemente cercanos a los tokens para este alcance reducido.
- Definir valores de `border`/`text-secondary` para modo oscuro — no existen en el design system todavía; se dejan iguales a los de modo claro hasta que se agregue esa decisión ahí.
