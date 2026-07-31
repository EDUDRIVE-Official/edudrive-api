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
                x-data="{ theme: document.documentElement.getAttribute('data-theme') }"
                x-init="$watch('theme', value => { document.documentElement.setAttribute('data-theme', value); localStorage.setItem('edudrive-theme', value); })"
                @click="theme = theme === 'dark' ? 'light' : 'dark'"
                :aria-pressed="theme === 'dark'"
                aria-label="Cambiar entre modo claro y oscuro"
                class="rounded-sm border border-border px-3 py-2 text-sm text-text hover:bg-surface focus-visible:outline-none focus-visible:shadow-focus"
            >
                <span x-text="theme === 'dark' ? 'Modo oscuro' : 'Modo claro'"></span>
            </button>
        </div>
        {{ $slot }}
    </div>
</body>
</html>
