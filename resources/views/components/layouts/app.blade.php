@props(['title' => 'EDUDRIVE'])
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
    <header class="border-b border-border bg-surface">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
            <span class="font-heading text-lg font-bold text-text">EDUDRIVE</span>
            <div class="flex items-center gap-4">
                @auth
                    <span class="font-sans text-sm text-text-secondary">
                        {{ auth()->user()->name }} ({{ auth()->user()->email }})
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary" size="sm">Cerrar sesión</x-ui.button>
                    </form>
                @endauth
                <button
                    type="button"
                    x-data
                    @click="
                        var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                        document.documentElement.setAttribute('data-theme', next);
                        localStorage.setItem('edudrive-theme', next);
                    "
                    class="rounded-sm border border-border px-3 py-2 text-sm text-text hover:bg-background focus-visible:outline-none focus-visible:shadow-focus"
                >
                    Cambiar tema
                </button>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-5xl px-6 py-8">
        {{ $slot }}
    </main>
</body>
</html>
