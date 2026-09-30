<x-layouts.app title="EDUDRIVE — Preparación del piloto P912">
    @php
        $specialties = ['education' => 'Docencia', 'road_safety' => 'Seguridad vial', 'accessibility' => 'Accesibilidad'];
        $steps = [
            ['key' => 'workspace', 'title' => '1. Curso borrador', 'route' => 'pilot-instruments.visual-candidates', 'action' => 'Abrir espacio editorial'],
            ['key' => 'reviewers', 'title' => '2. Personas revisoras', 'route' => 'pilot-instruments.visual-reviewers', 'action' => 'Gestionar designaciones'],
            ['key' => 'reviews', 'title' => '3. Cobertura de escenas', 'route' => 'pilot-instruments.visual-review-summary', 'action' => 'Ver cobertura'],
            ['key' => 'candidates', 'title' => '4. Candidaturas', 'route' => 'pilot-instruments.visual-candidates', 'action' => 'Vincular escenas'],
        ];
    @endphp

    <main id="pilot-main" class="mx-auto max-w-6xl space-y-7">
        <header class="space-y-3">
            <p class="text-sm font-semibold text-primary">Gobierno interno · Piloto Primaria 9–12</p>
            <h1 class="text-3xl font-bold">Preparación del piloto P912</h1>
            <p class="max-w-4xl text-text-secondary">Este tablero reúne las comprobaciones técnicas y editoriales que ya puede realizar EduDrive. Sirve para saber qué falta y quién debe actuar; no concede aprobación pedagógica, vial, accesible, ética ni regulatoria.</p>
            <div class="flex flex-wrap gap-3"><a class="inline-flex min-h-11 items-center rounded bg-primary px-4 font-semibold text-white" href="{{ route('pilot-instruments.review-coordination') }}">Coordinar revisiones</a><a class="inline-flex min-h-11 items-center rounded border border-primary px-4 font-semibold text-primary" href="{{ route('pilot-instruments.reviewer-onboarding') }}">Preparar incorporación de revisores</a><a class="inline-flex min-h-11 items-center rounded border border-primary px-4 font-semibold text-primary" href="{{ route('pilot-instruments.visual-review-guide') }}">Abrir guía para especialistas</a><a class="inline-flex min-h-11 items-center rounded border border-primary px-4 font-semibold text-primary" href="{{ route('pilot-instruments.pilot-dossier') }}">Abrir expediente institucional</a></div>
        </header>

        <section class="rounded-2xl border p-6 {{ $readiness['technical_preparation_complete'] ? 'border-green-500 bg-green-50 text-green-950' : 'border-amber-400 bg-amber-50 text-amber-950' }}">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-2">
                    <p class="text-sm font-semibold uppercase tracking-wide">Estado comprobable por el sistema</p>
                    <h2 class="text-2xl font-bold">{{ $readiness['technical_preparation_complete'] ? 'Preparación técnica completa' : 'Preparación técnica incompleta' }}</h2>
                    <p>{{ $readiness['technical_preparation_complete'] ? 'Los cuatro controles internos están completos. Esto habilita solicitar las validaciones externas; no autoriza publicar ni trabajar con estudiantes.' : 'Hay controles internos pendientes. Completalos antes de solicitar validación externa o planificar una aplicación con participantes.' }}</p>
                </div>
                <span class="rounded-full bg-white px-4 py-2 font-semibold shadow-sm">{{ collect(['workspace', 'reviewers', 'reviews', 'candidates'])->filter(fn ($key) => $readiness[$key]['complete'])->count() }} de 4 controles</span>
            </div>
        </section>

        <div class="grid gap-5 md:grid-cols-2">
            @foreach($steps as $step)
                @php($state = $readiness[$step['key']])
                <section class="flex flex-col justify-between gap-5 rounded-xl border border-border bg-surface p-5">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="text-xl font-bold">{{ $step['title'] }}</h2>
                            <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $state['complete'] ? 'bg-green-100 text-green-900' : 'bg-amber-100 text-amber-900' }}">{{ $state['complete'] ? 'Completo' : 'Pendiente' }}</span>
                        </div>

                        @if($step['key'] === 'workspace')
                            <p><strong>{{ $state['lessons'] }} de 4</strong> lecciones existen dentro del curso aislado P912 en borrador.</p>
                        @elseif($step['key'] === 'reviewers')
                            <p><strong>{{ count($state['active_specialties']) }} de 3</strong> especialidades tienen una designación activa.</p>
                            @if($state['missing_specialties'] !== [])<p class="text-sm">Faltan: {{ collect($state['missing_specialties'])->map(fn ($specialty) => $specialties[$specialty])->join(', ') }}.</p>@endif
                        @elseif($step['key'] === 'reviews')
                            <p><strong>{{ $state['completed_scenes'] }} de {{ $state['total_scenes'] }}</strong> escenas tienen cobertura completa de Docencia, Seguridad vial y Accesibilidad, sin cambios pendientes.</p>
                        @else
                            <p><strong>{{ $state['linked_scenes'] }} de {{ $state['total_scenes'] }}</strong> escenas revisadas están vinculadas como candidatas a las lecciones del borrador P912.</p>
                        @endif
                    </div>
                    <a class="inline-flex min-h-11 w-fit items-center rounded border border-primary px-4 font-semibold text-primary" href="{{ route($step['route']) }}">{{ $step['action'] }}</a>
                </section>
            @endforeach
        </div>

        <section class="space-y-4 rounded-2xl border border-blue-300 bg-blue-50 p-6 text-blue-950">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide">Fuera del alcance automático</p>
                <h2 class="mt-1 text-2xl font-bold">Validación externa pendiente</h2>
                <p class="mt-2">Aunque los cuatro controles internos estén completos, estas comprobaciones deben realizarlas personas e instituciones responsables y quedar documentadas.</p>
            </div>
            <ul class="grid gap-3 md:grid-cols-2">
                <li class="rounded-lg bg-white/80 p-4"><strong>Credenciales y mandato:</strong> comprobar la autenticidad de las designaciones y su respaldo institucional.</li>
                <li class="rounded-lg bg-white/80 p-4"><strong>Accesibilidad real:</strong> probar con tecnologías de apoyo, dispositivos y personas usuarias diversas.</li>
                <li class="rounded-lg bg-white/80 p-4"><strong>Ensayo docente:</strong> observar claridad, edad adecuada, mediación y carga de lectura en un entorno controlado.</li>
                <li class="rounded-lg bg-white/80 p-4"><strong>Protección de participantes:</strong> aprobar consentimiento, privacidad, datos, atención de incidentes y resguardo de menores.</li>
                <li class="rounded-lg bg-white/80 p-4"><strong>Piloto con usuarios:</strong> ejecutar una muestra controlada, registrar hallazgos y corregir antes de ampliar el alcance.</li>
                <li class="rounded-lg bg-white/80 p-4"><strong>Decisión institucional:</strong> las autoridades educativas y de seguridad vial determinan aprobación, publicación y escala.</li>
            </ul>
            <a class="inline-flex min-h-11 items-center rounded bg-blue-900 px-4 font-semibold text-white" href="{{ route('pilot-instruments.pilot-protocol') }}">Consultar protocolo del piloto controlado</a>
        </section>

        <aside class="rounded-lg border border-border bg-surface p-4"><strong>Regla de avance:</strong> EduDrive puede demostrar que los controles internos se completaron; nunca debe convertirlos automáticamente en una aprobación final.</aside>
    </main>
</x-layouts.app>
