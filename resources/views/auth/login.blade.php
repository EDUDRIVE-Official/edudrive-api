<x-layouts.app title="EDUDRIVE — Ingresar">
    <div class="mx-auto flex max-w-sm flex-col gap-6">
        <h1 class="font-heading text-2xl font-bold">Ingresar</h1>

        @if (session('loginError'))
            <p class="font-sans text-sm text-danger-text">{{ session('loginError') }}</p>
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
                <x-ui.button type="submit" variant="primary">Ingresar</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
