<x-layouts.app title="EDUDRIVE — Recuperar acceso">
    <main class="mx-auto flex max-w-md flex-col gap-6">
        <header class="text-center">
            <img src="{{ asset('brand/edudrive-mark.svg') }}" alt="" class="brand-logo-light mx-auto h-20 w-20" aria-hidden="true">
            <img src="{{ asset('brand/edudrive-mark-dark.svg') }}" alt="" class="brand-logo-dark mx-auto h-20 w-20" aria-hidden="true">
            <h1 class="mt-4 font-heading text-2xl font-bold">Definí tu contraseña</h1>
            <p class="mt-2 text-sm leading-6 text-text-secondary">Escribí el correo de tu cuenta. Se generará un enlace personal que expira en 60 minutos y solo puede utilizarse una vez.</p>
        </header>

        @if ($usesLocalMailbox)
            <p class="rounded border border-amber-400 bg-amber-50 p-4 text-sm text-amber-950"><strong>Entorno de pruebas:</strong> el mensaje se guarda en el buzón local de desarrollo y no llega al correo externo.</p>
        @endif

        @if (session('status'))
            <p role="status" class="rounded border border-blue-300 bg-blue-50 p-4 text-sm text-blue-950">{{ session('status') }}</p>
        @endif

        <x-ui.card>
            <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
                @csrf
                <x-ui.input name="email" type="email" label="Correo electrónico" value="{{ old('email') }}" autocomplete="email" required :error="$errors->first('email')" />
                <x-ui.button type="submit" variant="primary">Enviar enlace seguro</x-ui.button>
            </form>
        </x-ui.card>

        <p class="text-center text-sm"><a href="{{ route('login') }}" class="font-semibold text-primary underline">Volver al inicio de sesión</a></p>
    </main>
</x-layouts.app>
