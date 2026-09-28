<x-layouts.app title="EDUDRIVE — Crear contraseña">
    <main class="mx-auto flex max-w-md flex-col gap-6">
        <header class="text-center">
            <img src="{{ asset('brand/edudrive-mark.svg') }}" alt="" class="brand-logo-light mx-auto h-20 w-20" aria-hidden="true">
            <img src="{{ asset('brand/edudrive-mark-dark.svg') }}" alt="" class="brand-logo-dark mx-auto h-20 w-20" aria-hidden="true">
            <h1 class="mt-4 font-heading text-2xl font-bold">Creá tu contraseña</h1>
            <p class="mt-2 text-sm leading-6 text-text-secondary">Usá al menos 12 caracteres. Al guardar, el enlace deja de funcionar y las sesiones anteriores se cierran.</p>
        </header>

        <x-ui.card>
            <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <x-ui.input name="email" type="email" label="Correo electrónico" value="{{ old('email', $email) }}" autocomplete="email" required :error="$errors->first('email')" />
                <x-ui.input name="password" type="password" label="Nueva contraseña" autocomplete="new-password" minlength="12" required :error="$errors->first('password')" />
                <x-ui.input name="password_confirmation" type="password" label="Confirmá la nueva contraseña" autocomplete="new-password" minlength="12" required />
                @if ($errors->has('token'))<p class="text-sm text-danger-text">{{ $errors->first('token') }}</p>@endif
                <x-ui.button type="submit" variant="primary">Guardar contraseña e ingresar</x-ui.button>
            </form>
        </x-ui.card>
    </main>
</x-layouts.app>
