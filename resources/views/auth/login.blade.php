<x-layouts.app title="EDUDRIVE — Ingresar">
    <div class="ed-ingreso">
        <section class="ed-ingreso-izq" aria-labelledby="ingreso-titulo">
            <div>
                <h1 id="ingreso-titulo">Ingresá a EDUDRIVE</h1>
                <p>Aprendé a observar, decidir y convivir con seguridad en cada etapa de tu vida.</p>

                <div class="ed-previa" role="group" aria-labelledby="previa-titulo">
                    <h2 id="previa-titulo">Un recorrido para cada etapa</h2>
                    <ul class="ed-previa-fila">
                        @foreach ([['E1', 'DESCUBRO', '3–6 años'], ['E2', 'COMPRENDO', '7–12 años'], ['E3', 'DECIDO', '13–16 años'], ['E4', 'CONDUZCO', '17+ años']] as [$code, $name, $ages])
                            <li>
                                <x-ui.stage-plate :stage="$code" size="sm" />
                                <span class="ed-previa-nombre">{{ $name }}</span>
                                <span class="ed-previa-edad">{{ $ages }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <span class="ed-calzada" aria-hidden="true"></span>
                </div>
            </div>
        </section>

        <section class="ed-ingreso-der" aria-labelledby="acceso-titulo">
            <div class="flex w-full max-w-md flex-col gap-5">
                <h2 id="acceso-titulo" class="text-3xl font-extrabold">Tus datos de acceso</h2>

                @if (session('loginError'))
                    <p class="font-sans text-base font-bold text-danger-text">{{ session('loginError') }}</p>
                @endif

                @if (session('passwordResetStatus'))
                    <p role="status" class="rounded-sm border-[3px] border-success bg-success/10 p-3 font-sans text-base font-semibold text-success-text">{{ session('passwordResetStatus') }}</p>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}" class="flex flex-col gap-5">
                    @csrf
                    <x-ui.input
                        name="email"
                        type="email"
                        label="Correo electrónico"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        :error="$errors->first('email')"
                    />
                    <x-ui.input
                        name="password"
                        type="password"
                        label="Contraseña"
                        autocomplete="current-password"
                        :error="$errors->first('password')"
                    />
                    <x-ui.button type="submit" variant="primary" size="lg">Continuar mi recorrido</x-ui.button>
                    <a href="{{ route('password.request') }}" class="inline-flex min-h-12 items-center justify-center text-center text-base font-semibold text-text underline decoration-accent decoration-[3px] underline-offset-4 hover:decoration-text">Olvidé mi contraseña o necesito crearla</a>
                </form>
                <p class="text-sm text-text-secondary">Educación vial para la vida.</p>
            </div>
        </section>
    </div>
</x-layouts.app>
