<x-layouts.app title="EDUDRIVE — Verificar certificado">
    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        <div class="text-center">
            <img src="{{ asset('brand/edudrive-mark.svg') }}" alt="" class="brand-logo-light mx-auto h-16 w-16" aria-hidden="true">
            <img src="{{ asset('brand/edudrive-mark-dark.svg') }}" alt="" class="brand-logo-dark mx-auto h-16 w-16" aria-hidden="true">
            <p class="mt-4 text-xs font-bold uppercase tracking-[0.16em] text-secondary">Consulta pública</p>
            <h1 class="mt-1 font-heading text-3xl font-bold">Verificar certificado</h1>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-text-secondary">Ingresá el código de la credencial para confirmar que fue emitida por EDUDRIVE y consultar su vigencia actual.</p>
        </div>

        <x-ui.card>
            <form method="GET" action="{{ route('certificates.verify') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-ui.input name="code" label="Código de validación" value="{{ $code }}" placeholder="ABCD-EFGH-IJKL" required />
                </div>
                <x-ui.button type="submit">Verificar</x-ui.button>
            </form>
        </x-ui.card>

        @if ($notFound)
            <x-ui.card class="border border-danger">
                <p class="font-sans text-sm text-danger-text">No encontramos un certificado con ese código.</p>
            </x-ui.card>
        @endif

        @if ($certificate)
            @php
                $statusLabels = ['valid' => 'Válido', 'expired' => 'Vencido', 'revoked' => 'Revocado'];
                $statusVariants = ['valid' => 'success', 'expired' => 'warning', 'revoked' => 'danger'];
            @endphp
            <section class="overflow-hidden rounded-xl border border-border bg-surface shadow-md">
                <div class="h-2 {{ $certificate['status'] === 'valid' ? 'bg-success' : ($certificate['status'] === 'expired' ? 'bg-warning' : 'bg-danger') }}" aria-hidden="true"></div>
                <div class="p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <span class="font-sans text-sm text-text-secondary">Curso certificado</span>
                        <h2 class="font-heading text-xl font-bold">{{ $certificate['course_name'] }}</h2>
                    </div>
                    <x-ui.badge :variant="$statusVariants[$certificate['status']] ?? 'info'">
                        {{ $statusLabels[$certificate['status']] ?? $certificate['status'] }}
                    </x-ui.badge>
                </div>
                <div class="mt-4 rounded-lg border p-4 {{ $certificate['status'] === 'valid' ? 'border-success/30 bg-success/10' : 'border-warning/30 bg-warning/10' }}">
                    <p class="text-xs font-bold uppercase tracking-wide {{ $certificate['status'] === 'valid' ? 'text-success-text' : 'text-warning-text' }}">Resultado de la verificación</p>
                    <p class="mt-1 font-heading text-lg font-bold text-text">
                        @if ($certificate['status'] === 'valid') La credencial es auténtica y está vigente.
                        @elseif ($certificate['status'] === 'expired') La credencial es auténtica, pero su vigencia terminó.
                        @else La credencial es auténtica, pero fue revocada. @endif
                    </p>
                </div>
                <dl class="mt-4 grid gap-3 font-sans text-sm sm:grid-cols-2">
                    <div><dt class="text-text-secondary">Titular</dt><dd>{{ $certificate['holder_name'] ?? 'Identidad protegida' }}</dd></div>
                    <div><dt class="text-text-secondary">Código</dt><dd>{{ $certificate['validation_code'] }}</dd></div>
                    <div><dt class="text-text-secondary">Emitido</dt><dd>{{ $certificate['issued_at'] }}</dd></div>
                    <div><dt class="text-text-secondary">Vence</dt><dd>{{ $certificate['expires_at'] ?? 'Sin vencimiento' }}</dd></div>
                    @if ($certificate['course_duration_hours'] ?? null)<div><dt class="text-text-secondary">Duración formativa</dt><dd>{{ $certificate['course_duration_hours'] }} hora{{ $certificate['course_duration_hours'] === 1 ? '' : 's' }}</dd></div>@endif
                </dl>
                @if ($certificate['course_objectives'] ?? null)
                    <div class="mt-4 border-t border-border pt-4"><p class="text-xs font-bold uppercase tracking-wide text-primary">Alcance del aprendizaje certificado</p><p class="mt-1 text-sm leading-6 text-text-secondary">{{ $certificate['course_objectives'] }}</p></div>
                @endif
                <div class="mt-4 rounded-lg border border-primary/20 bg-primary/5 p-4 text-sm leading-6 text-text-secondary">
                    <p><strong class="text-text">¿Qué confirma esta página?</strong> Que EDUDRIVE emitió la constancia indicada, para el curso y la persona mostrados, y que su estado actual es el señalado.</p>
                    <p class="mt-2">La constancia acredita educación vial. No equivale a una licencia o permiso oficial para conducir.</p>
                </div>
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
