<x-layouts.app title="EDUDRIVE — Mis consentimientos">
    @php
        $policyCollection = collect($policies);
        $activeCount = $policyCollection->filter(fn (array $policy): bool => ! empty($policy['active_consent']))->count();
        $policyInformation = [
            'privacy_policy' => ['title' => 'Política de privacidad', 'icon' => '🛡️', 'summary' => 'Explica cómo se protegen y utilizan los datos necesarios para ofrecer la experiencia educativa, el progreso y el Pasaporte Vial.'],
            'terms_of_service' => ['title' => 'Condiciones de uso', 'icon' => '📄', 'summary' => 'Establece las reglas para utilizar EDUDRIVE de manera segura, responsable y respetuosa con otras personas.'],
            'minor_consent' => ['title' => 'Consentimiento para menores', 'icon' => '🤝', 'summary' => 'Registra la autorización y el acompañamiento requerido cuando participa una persona menor de edad.'],
        ];
    @endphp
    <div class="mx-auto flex max-w-4xl flex-col gap-6">
        <section class="campus-hero relative overflow-hidden rounded-xl bg-[#0b3a6e] px-6 py-8 text-white shadow-md sm:px-8" aria-labelledby="consents-title">
            <div class="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-[#008a78]/55" aria-hidden="true"></div>
            <div class="absolute -bottom-24 right-32 h-48 w-48 rounded-full bg-[#f5b700]/20" aria-hidden="true"></div>
            <div class="relative flex flex-col justify-between gap-6 sm:flex-row sm:items-center">
                <div class="max-w-2xl"><p class="text-sm font-bold uppercase tracking-[0.16em] text-[#5bd6c0]">Privacidad y decisiones informadas</p><h1 id="consents-title" class="mt-2 font-heading text-3xl font-bold sm:text-4xl">Mis consentimientos</h1><p class="mt-3 text-sm leading-6 text-white/85">Revisá qué políticas están vigentes y mantené el control sobre las autorizaciones asociadas con tu cuenta.</p></div>
                <div class="grid shrink-0 grid-cols-2 gap-3 text-center"><div class="rounded-xl bg-white/10 px-5 py-3"><p class="font-heading text-3xl font-bold text-[#5bd6c0]">{{ $activeCount }}</p><p class="text-xs text-white/70">Aceptadas</p></div><div class="rounded-xl bg-white/10 px-5 py-3"><p class="font-heading text-3xl font-bold text-[#ffd45a]">{{ count($policies) - $activeCount }}</p><p class="text-xs text-white/70">Pendientes</p></div></div>
            </div>
        </section>

        <aside class="rounded-xl border border-primary/25 bg-primary/5 p-5" aria-label="Información sobre consentimientos">
            <div class="flex gap-3"><span class="text-2xl" aria-hidden="true">ℹ️</span><div><h2 class="font-heading font-bold">Una decisión debe ser comprensible y voluntaria</h2><p class="mt-1 text-sm leading-6 text-text-secondary">Antes de aceptar, revisá el propósito, la versión y la fecha de vigencia. Podés revocar un consentimiento desde esta pantalla; algunas funciones que dependan de él podrían dejar de estar disponibles.</p></div></div>
        </aside>
        @if (session('status'))
            <p class="text-sm text-success-text">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p class="text-sm text-danger-text">{{ session('error') }}</p>
        @endif

        @forelse ($policies as $policy)
            @php $policyInfo = $policyInformation[$policy['key']] ?? ['title' => str($policy['key'])->replace('_', ' ')->title(), 'icon' => '📋', 'summary' => 'Política vigente asociada con el uso de EDUDRIVE.']; @endphp
            <article class="overflow-hidden rounded-xl border border-border bg-surface shadow-sm">
                <div class="h-1.5 {{ $policy['active_consent'] ? 'bg-success' : 'bg-warning' }}" aria-hidden="true"></div>
                <div class="p-5 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex gap-4"><span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-2xl" aria-hidden="true">{{ $policyInfo['icon'] }}</span><div><h2 class="font-heading text-lg font-bold">{{ $policyInfo['title'] }}</h2><p class="mt-1 text-sm text-text-secondary">Versión {{ $policy['version'] }} · vigente desde {{ \Illuminate\Support\Carbon::parse($policy['effective_at'])->format('d/m/Y') }}</p></div></div>
                    <x-ui.badge :variant="$policy['active_consent'] ? 'success' : 'warning'">{{ $policy['active_consent'] ? 'Aceptada' : 'Pendiente' }}</x-ui.badge>
                </div>
                <p class="mt-4 rounded-lg border border-border bg-background p-4 text-sm leading-6 text-text-secondary">{{ $policyInfo['summary'] }}</p>
                @if ($policy['active_consent'])
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3"><p class="text-xs text-text-secondary">Consentimiento registrado para esta versión vigente.</p><form method="POST" action="{{ route('legal.consents.revoke', $policy['key']) }}" onsubmit="return confirm('¿Seguro que querés revocar este consentimiento?');">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="danger" size="sm">Revocar consentimiento</x-ui.button>
                    </form></div>
                @else
                    <form method="POST" action="{{ route('legal.consents.accept') }}" class="mt-4 space-y-3">
                        @csrf
                        <input type="hidden" name="policy_key" value="{{ $policy['key'] }}">
                        <x-ui.input name="guardian_declaration" id="guardian-declaration-{{ $policy['key'] }}" label="Nombre de la madre, padre o persona tutora (obligatorio para menores)" maxlength="150" />
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-border bg-background p-3 text-sm text-text-secondary"><input type="checkbox" required class="mt-1"><span>Confirmo que comprendí el propósito de esta política y deseo aceptar su versión vigente.</span></label>
                        <x-ui.button type="submit">Aceptar política vigente</x-ui.button>
                    </form>
                @endif
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-border bg-surface p-8 text-center"><p class="text-4xl" aria-hidden="true">📄</p><p class="mt-3 text-sm text-text-secondary">No hay políticas vigentes publicadas.</p></div>
        @endforelse

        @if (count($consentHistory) > 0)
            <x-ui.card>
                <h2 class="mb-3 font-heading text-lg font-bold">Historial</h2>
                <x-ui.table>
                    <x-slot:head><tr><th class="px-4 py-2">Política</th><th class="px-4 py-2">Versión</th><th class="px-4 py-2">Aceptado</th><th class="px-4 py-2">Estado</th></tr></x-slot:head>
                    @foreach ($consentHistory as $consent)
                        <tr><td class="px-4 py-2">{{ $policyInformation[$consent['policy_key']]['title'] ?? str($consent['policy_key'])->replace('_', ' ')->title() }}</td><td class="px-4 py-2">{{ $consent['policy_version'] }}</td><td class="px-4 py-2">{{ $consent['accepted_at'] }}</td><td class="px-4 py-2"><span class="font-semibold {{ $consent['revoked_at'] ? 'text-warning-text' : 'text-success-text' }}">{{ $consent['revoked_at'] ? 'Revocado' : 'Activo' }}</span></td></tr>
                    @endforeach
                </x-ui.table>
            </x-ui.card>
        @endif
    </div>
</x-layouts.app>
