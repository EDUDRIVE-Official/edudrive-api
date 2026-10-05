<x-layouts.app title="EDUDRIVE — Recuperar acceso">
    <div class="mx-auto flex max-w-md flex-col gap-6">
        <header class="text-center">
            <img src="{{ asset('brand/edudrive-mark.svg') }}" alt="" class="brand-logo-light mx-auto h-20 w-20" aria-hidden="true">
            <img src="{{ asset('brand/edudrive-mark-dark.svg') }}" alt="" class="brand-logo-dark mx-auto h-20 w-20" aria-hidden="true">
            <h1 class="mt-4 font-heading text-2xl font-bold">Definí tu contraseña</h1>
            <p class="mt-2 text-base leading-6 text-text-secondary">Escribí el correo de tu cuenta. Se generará un enlace personal que expira en 60 minutos y solo puede utilizarse una vez.</p>
        </header>

        @if ($usesLocalMailbox)
            <p class="rounded-sm border-[3px] border-warning bg-warning/10 p-4 text-base text-text"><strong>Entorno de pruebas:</strong> el mensaje se guarda en el buzón local de desarrollo y no llega al correo externo.</p>
        @endif

        @if (session('status'))
            <p role="status" class="rounded-sm border-[3px] border-info bg-info/10 p-4 text-base text-text">{{ session('status') }}</p>
        @endif

        <x-ui.card>
            <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
                @csrf
                <x-ui.input name="email" type="email" label="Correo electrónico" value="{{ old('email') }}" autocomplete="email" required :error="$errors->first('email')" />
                <x-ui.button type="submit" variant="primary">Enviar enlace seguro</x-ui.button>
            </form>
        </x-ui.card>

        <p class="text-center text-sm"><a href="{{ route('login') }}" class="inline-flex min-h-12 items-center font-semibold text-text underline decoration-accent decoration-[3px] underline-offset-4">Volver al inicio de sesión</a></p>
    </div>
</x-layouts.app>
