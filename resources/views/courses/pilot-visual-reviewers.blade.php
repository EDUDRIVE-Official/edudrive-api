<x-layouts.app title="EDUDRIVE — Revisores visuales">
    @php($specialties = ['education' => 'Docencia', 'road_safety' => 'Seguridad vial', 'accessibility' => 'Accesibilidad'])
    <div class="mx-auto max-w-5xl space-y-6">
        <header class="space-y-2">
            <p class="text-sm">Gobierno interno del piloto P912</p>
            <h1 class="text-3xl font-bold">Designación de personas revisoras</h1>
            <p>La designación habilita una cuenta para registrar una sola especialidad. No valida automáticamente títulos, experiencia ni representación institucional.</p>
            <div class="flex flex-wrap gap-3"><a class="inline-flex min-h-11 items-center rounded bg-primary px-4 font-semibold text-white" href="{{ route('pilot-instruments.review-coordination') }}">Coordinar revisiones</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.reviewer-onboarding') }}">Incorporar revisores</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.visual-review-guide') }}">Guía para especialistas</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.visual-review-summary') }}">Volver a cobertura</a></div>
        </header>

        @if(session('status'))<p class="rounded border border-green-500 bg-green-50 p-4 text-green-950" role="status">{{ session('status') }}</p>@endif
        @if($errors->any())<div class="rounded border border-red-500 bg-red-50 p-4 text-red-950" role="alert"><strong>No se guardó la designación.</strong><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <section class="space-y-4 rounded-xl border border-border bg-surface p-5">
            <h2 class="text-xl font-bold">Registrar o actualizar una designación</h2>
            <form class="grid gap-4 md:grid-cols-2" method="POST" action="{{ route('pilot-instruments.visual-reviewers.store') }}">
                @csrf
                <label class="space-y-1"><span class="font-semibold">Cuenta</span><select required class="w-full rounded border border-border bg-background p-3" name="user_id"><option value="">Seleccioná una cuenta activa</option>@foreach($eligibleUsers as $user)<option value="{{ $user['id'] }}">{{ $user['name'] }} · {{ $user['email'] }}</option>@endforeach</select></label>
                <label class="space-y-1"><span class="font-semibold">Especialidad autorizada</span><select required class="w-full rounded border border-border bg-background p-3" name="specialty"><option value="">Seleccioná</option>@foreach($specialties as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                <label class="space-y-1"><span class="font-semibold">Organización que respalda</span><input required maxlength="180" class="w-full rounded border border-border bg-background p-3" name="organization"></label>
                <label class="space-y-1"><span class="font-semibold">Fecha de designación</span><input required type="date" value="{{ now()->toDateString() }}" class="w-full rounded border border-border bg-background p-3" name="designated_on"></label>
                <label class="space-y-1 md:col-span-2"><span class="font-semibold">Base o cualificación declarada</span><textarea required maxlength="2000" rows="3" class="w-full rounded border border-border bg-background p-3" name="qualification" placeholder="Función, experiencia o criterio usado para la designación. No incluyás datos sensibles."></textarea></label>
                <label class="space-y-1 md:col-span-2"><span class="font-semibold">Referencia verificable</span><input required maxlength="500" class="w-full rounded border border-border bg-background p-3" name="evidence_reference" placeholder="Código de oficio, expediente interno o documento de respaldo"></label>
                <fieldset><legend class="font-semibold">Estado</legend><div class="mt-2 flex gap-5"><label><input required type="radio" name="active" value="1" checked> Activa</label><label><input required type="radio" name="active" value="0"> Inactiva</label></div></fieldset>
                <div class="md:col-span-2"><button class="min-h-11 rounded bg-primary px-5 py-3 font-semibold text-white" type="submit">Guardar designación</button></div>
            </form>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold">Designaciones registradas</h2>
            @forelse($designations as $designation)
                <article class="rounded-xl border border-border bg-surface p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3"><div><h3 class="font-bold">{{ $designation['name'] }}</h3><p>{{ $designation['email'] }}</p></div><span class="rounded-full px-3 py-1 text-sm {{ $designation['active'] ? 'bg-green-100 text-green-900' : 'bg-slate-200 text-slate-800' }}">{{ $designation['active'] ? 'Activa' : 'Inactiva' }}</span></div>
                    <dl class="mt-3 grid gap-2 md:grid-cols-2"><div><dt class="font-semibold">Especialidad</dt><dd>{{ $specialties[$designation['specialty']] }}</dd></div><div><dt class="font-semibold">Organización</dt><dd>{{ $designation['organization'] }}</dd></div><div><dt class="font-semibold">Fecha</dt><dd>{{ $designation['designated_on'] }}</dd></div><div><dt class="font-semibold">Referencia</dt><dd>{{ $designation['evidence_reference'] }}</dd></div><div class="md:col-span-2"><dt class="font-semibold">Base declarada</dt><dd>{{ $designation['qualification'] }}</dd></div></dl>
                </article>
            @empty
                <p class="rounded border border-amber-400 bg-amber-50 p-4 text-amber-950">Todavía no existen personas revisoras designadas.</p>
            @endforelse
        </section>

        <details class="rounded-xl border border-border bg-surface p-5">
            <summary class="cursor-pointer text-xl font-bold">Historial inalterable de designaciones ({{ count($designationHistory) }})</summary>
            <div class="mt-4 space-y-3">
                @forelse($designationHistory as $event)
                    <article class="rounded border border-border p-3"><p class="font-semibold">{{ $event['name'] }} · {{ $specialties[$event['specialty']] }} · {{ $event['active'] ? 'Activada' : 'Inactivada' }}</p><p>{{ $event['organization'] }} · {{ $event['designated_on'] }} · {{ $event['evidence_reference'] }}</p><p class="text-sm text-text-secondary">Registrada {{ $event['created_at'] }}</p></article>
                @empty
                    <p>No hay eventos registrados.</p>
                @endforelse
            </div>
        </details>

        <aside class="rounded border border-amber-400 bg-amber-50 p-4 text-amber-950"><strong>Límite:</strong> el sistema registra quién autorizó la participación y con qué referencia. La autenticidad del respaldo debe comprobarse fuera de este prototipo.</aside>
    </div>
</x-layouts.app>
