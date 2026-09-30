<x-layouts.app title="EDUDRIVE — Ingresar">
    <div class="mx-auto flex max-w-sm flex-col gap-6">
        <div class="text-center">
            <img src="{{ asset('brand/edudrive-mark.svg') }}" alt="" class="brand-logo-light mx-auto h-20 w-20" aria-hidden="true">
            <img src="{{ asset('brand/edudrive-mark-dark.svg') }}" alt="" class="brand-logo-dark mx-auto h-20 w-20" aria-hidden="true">
            <h1 class="mt-4 font-heading text-2xl font-bold">Ingresá a EDUDRIVE</h1>
            <p class="mt-2 text-sm leading-6 text-text-secondary">Aprendé a observar, decidir y convivir con seguridad en cada etapa de tu vida.</p>
        </div>

        @if (session('loginError'))
            <p class="font-sans text-sm text-danger-text">{{ session('loginError') }}</p>
        @endif

        @if (session('passwordResetStatus'))
            <p role="status" class="rounded border border-green-300 bg-green-50 p-3 font-sans text-sm text-green-900">{{ session('passwordResetStatus') }}</p>
        @endif

        <x-ui.card>
            <form method="POST" action="{{ route('login.attempt') }}" class="flex flex-col gap-4">
                @csrf
                <x-ui.input
                    name="email"
                    type="email"
                    label="Correo electrónico"
                    value="{{ old('email') }}"
                    :error="$errors->first('email')"
                />
                <x-ui.input
                    name="password"
                    type="password"
                    label="Contraseña"
                    :error="$errors->first('password')"
                />
                <x-ui.button type="submit" variant="primary">Continuar mi recorrido</x-ui.button>
                <a href="{{ route('password.request') }}" class="text-center text-sm font-semibold text-primary underline">Olvidé mi contraseña o necesito crearla</a>
            </form>
        </x-ui.card>
        <p class="text-center text-xs text-text-secondary">Educación vial para la vida.</p>
    </div>
</x-layouts.app>
