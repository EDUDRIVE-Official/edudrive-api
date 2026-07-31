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
