<x-layouts.app title="EDUDRIVE — Verificar Pasaporte Vial">
    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        <div>
            <h1 class="font-heading text-2xl font-bold">Verificar Pasaporte Vial</h1>
            <p class="mt-1 text-sm text-text-secondary">Confirmá la autenticidad y vigencia de un resumen educativo emitido por EDUDRIVE.</p>
        </div>

        <x-ui.card>
            <form method="GET" action="{{ route('road-passport.verify') }}" class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
                <x-ui.input name="code" label="Código de verificación" value="{{ $code }}" required />
                <x-ui.button type="submit">Verificar</x-ui.button>
            </form>
        </x-ui.card>

        @if ($notFound)
            <x-ui.card class="border border-danger/40">
                <p class="text-sm font-medium text-danger-text">El código no es válido o el Pasaporte Vial ya no está disponible.</p>
            </x-ui.card>
        @endif

        @if ($passport)
            @php
                $statusLabels = ['active' => 'Activo', 'suspended' => 'Suspendido', 'revoked' => 'Revocado'];
                $statusVariants = ['active' => 'success', 'suspended' => 'warning', 'revoked' => 'danger'];
                $statusMessages = [
                    'active' => 'El Pasaporte Vial está vigente al momento de esta consulta.',
                    'suspended' => 'El Pasaporte Vial es auténtico, pero está suspendido temporalmente y no debe presentarse como vigente.',
                    'revoked' => 'El Pasaporte Vial es auténtico, pero fue revocado y no está vigente.',
                ];
                $statusStyles = ['active' => 'border-success/30 bg-success/10', 'suspended' => 'border-warning/30 bg-warning/10', 'revoked' => 'border-danger/30 bg-danger/10'];
            @endphp
            <x-ui.card>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div><p class="text-xs font-bold uppercase tracking-wide text-primary">Resultado de la verificación</p><h2 class="mt-1 font-heading text-xl font-bold">Pasaporte auténtico</h2></div>
                    <x-ui.badge :variant="$statusVariants[$passport['status']] ?? 'info'">{{ $statusLabels[$passport['status']] ?? $passport['status'] }}</x-ui.badge>
                </div>
                <div class="mt-4 rounded-lg border p-4 text-sm font-medium text-text {{ $statusStyles[$passport['status']] ?? 'border-border bg-background' }}">
                    {{ $statusMessages[$passport['status']] ?? 'Consultá el estado mostrado antes de utilizar este resumen.' }}
                </div>
                <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-text-secondary">Nivel educativo</dt><dd class="font-bold">Nivel {{ $passport['level'] }}</dd></div>
                    <div><dt class="text-text-secondary">Emitido</dt><dd class="font-bold">{{ $passport['issued_at'] }}</dd></div>
                    <div><dt class="text-text-secondary">Confianza del recorrido</dt><dd class="font-bold">{{ $passport['trust_score'] }}/100</dd></div>
                    <div><dt class="text-text-secondary">Aprendizajes digitales</dt><dd class="font-bold">{{ $passport['digital_count'] }}</dd></div>
                    <div><dt class="text-text-secondary">Prácticas observadas</dt><dd class="font-bold">{{ $passport['observed_practice_count'] }}</dd></div>
                    <div><dt class="text-text-secondary">Reflexiones</dt><dd class="font-bold">{{ $passport['reflection_count'] }}</dd></div>
                    <div><dt class="text-text-secondary">Última evidencia</dt><dd class="font-bold">{{ $passport['latest_evidence_at'] }}</dd></div>
                    <div><dt class="text-text-secondary">Verificado en línea</dt><dd class="font-bold">{{ $passport['verified_at'] }}</dd></div>
                </dl>
                <p class="mt-4 text-xs leading-5 text-text-secondary">La autenticidad del registro no certifica dominio de una conducta ni habilita para conducir. El puntaje resume actividad educativa y antigüedad, no seguridad demostrada. El estado corresponde al momento indicado. Para comprobarlo nuevamente, abrí otra vez el enlace original o volvé a ingresar el código.</p>
                <div class="mt-5 rounded-lg border border-primary/20 bg-primary/5 p-4 text-sm leading-6 text-text-secondary">
                    <p>Esta consulta protege la identidad: no publica el nombre, correo, edad, reflexiones ni observaciones personales del titular.</p>
                    <p class="mt-2"><strong class="text-text">Alcance:</strong> confirma un recorrido de educación vial. No equivale a una licencia, permiso de circulación ni certificación gubernamental.</p>
                </div>
            </x-ui.card>
        @endif
    </div>
</x-layouts.app>
