@php
    $pageNames = array_merge(array_column(array_column($lesson['blocks'], 'payload'), 'title'), ['Lo que te llevás']);
    $pageUrl = fn (int $step) => route('pilot-instruments.editorial-preview', ['lesson' => $number, 'page' => $step]).'#lesson-page';
@endphp
<header class="space-y-4">
    <a href="{{ url('/courses') }}" class="underline">Volver a cursos</a>
    <p class="text-sm text-text-secondary">Lección {{ $number }} de {{ count($lessons) }} · Propuesta para 9–12 años con acompañamiento</p>
    <h1 class="text-3xl font-bold">{{ $lesson['title'] }}</h1>
    <details class="rounded-lg border border-border bg-surface p-4">
        <summary class="cursor-pointer font-semibold">Sobre esta prueba y otras lecciones</summary>
        <div class="mt-4 space-y-4">
            <p>Borrador para administradores · No publicado. Las respuestas no se guardan, no completan lecciones ni generan certificados. Revisión especializada pendiente.</p>
            <p>Podés avanzar y volver libremente. Si salís de una página de práctica, sus respuestas y animaciones se reinician al volver.</p>
            <nav aria-label="Lecciones del borrador" class="flex flex-wrap gap-3">
                @foreach($lessons as $item)
                    <a href="{{ route('pilot-instruments.editorial-preview', ['lesson' => $loop->iteration]) }}" class="rounded border border-border p-3 underline" @if($number === $loop->iteration) aria-current="page" @endif>{{ $loop->iteration }}. {{ $item['title'] }}</a>
                @endforeach
            </nav>
        </div>
    </details>
</header>
<section id="lesson-page" aria-labelledby="page-title" tabindex="-1" class="rounded-xl border border-border bg-surface p-6 sm:p-10 space-y-8">
    <header class="space-y-3">
        <p class="font-semibold text-text-secondary">Página {{ $page }} de 5 · {{ $page <= 1 ? 'Descubrí' : ($page <= 3 ? 'Observá y decidí' : ($page === 4 ? 'Explicá' : 'Recordá')) }}</p>
        <progress value="{{ $page }}" max="5" aria-label="Posición en la lección, página {{ $page }} de 5" class="h-2 w-full">{{ $page }} de 5</progress>
        <h2 id="page-title" class="text-2xl sm:text-3xl font-bold">{{ $pageNames[$page - 1] }}</h2>
    </header>

    @if($page === 1)
        <p class="text-xl leading-relaxed">{{ $lesson['instruction'] }}</p>
        <div class="max-w-prose space-y-5 text-lg leading-relaxed">
            @foreach(explode("\n\n", $lesson['blocks'][0]['payload']['markdown']) as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>
        <aside aria-label="Dos pistas para observar" class="grid gap-5 sm:grid-cols-2">
            @foreach($support['clues'] as [$title, $explanation])
            <div class="rounded-lg border border-border p-5 space-y-3">
                <h3 class="text-xl font-semibold">{{ $loop->iteration }}. {{ $title }}</h3>
                <p class="text-lg leading-relaxed">{{ $explanation }}</p>
            </div>
            @endforeach
        </aside>
        <p class="text-lg leading-relaxed"><strong>Tu misión:</strong> {{ $support['mission'] }}</p>
        <details class="rounded-lg border border-border p-5">
            <summary class="cursor-pointer text-lg font-semibold">Quiero saber más</summary>
            <p class="mt-4 text-lg leading-relaxed">{{ $support['more'] }}</p>
        </details>
    @elseif($page === 2 || $page === 3)
            <p class="text-lg leading-relaxed">Abrí la práctica 3D, observá la escena y elegí una respuesta. Después leé qué ocurrió. Podés probar otra opción antes de seguir.</p>
        @php($block = ['id' => 'editorial-'.$number.'-'.$page, 'payload' => $lesson['blocks'][$page - 1]['payload']])
        @if($number >= 4)
            @php($passengerMode = $number === 4 ? ($page === 2 ? 'moving' : 'boarding') : ($page === 2 ? 'descent' : 'after'))
            @include('courses.blocks.passenger-preview-3d', ['block' => $block, 'passengerMode' => $passengerMode])
        @else
            @include('courses.blocks.scenario', ['block' => $block, 'answerField' => false, 'uniformEditorialFeedback' => true])
        @endif
        <aside class="rounded-lg border border-border p-5 space-y-3">
            <h3 class="text-xl font-semibold">Contalo con tus palabras</h3>
            <p class="text-lg leading-relaxed">{{ $support['reflection'][$page - 2] }}</p>
            <p class="text-text-secondary">Podés responder en voz alta o señalar en la escena. No tenés que escribir ni salir a una calle real.</p>
        </aside>
    @elseif($page === 4)
        <div class="max-w-prose space-y-5 text-lg leading-relaxed">
            @foreach(explode("\n\n", $lesson['blocks'][3]['payload']['markdown']) as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>
        <aside class="rounded-lg border border-border p-6 space-y-4 text-lg leading-relaxed">
            <h3 class="text-xl font-semibold">Tres cosas para mostrar</h3>
            <ol class="list-decimal pl-6 space-y-4">
                @foreach($support['activity'] as $step)<li>{{ $step }}</li>@endforeach
            </ol>
        </aside>
        <p class="text-lg leading-relaxed">Si una pista cuesta, pedile al acompañante que te ayude a encontrarla y volvé a explicarla con tus palabras. Pedir ayuda también forma parte de aprender.</p>
    @else
        <p class="text-xl leading-relaxed">{{ $support['takeaway'] }}</p>
        <ul class="list-disc pl-6 space-y-5 text-lg leading-relaxed">
            @foreach($support['remember'] as $point)<li>{{ $point }}</li>@endforeach
        </ul>
        <aside class="rounded-lg border border-border p-6 space-y-4 text-lg leading-relaxed">
            <h3 class="text-xl font-semibold">Una situación nueva para conversar</h3>
            <p>{{ $lesson['transfer']['case'] }}</p>
            <p>{{ $lesson['transfer']['prompt'] }}</p>
            <p>Imaginen o dibujen la situación dentro del aula o en casa. No necesitan comprobarla en una calle real.</p>
        </aside>
        <p class="text-text-secondary">Llegaste al cierre del recorrido de prueba. Esto no es una evaluación aprobada ni una autorización para cruzar sin acompañamiento. No se ha registrado progreso.</p>
    @endif

    <nav aria-label="Páginas de la lección" class="flex flex-wrap items-center justify-between gap-4 border-t border-border pt-6">
        @if($page > 1)
            <a class="rounded-lg border border-border px-6 py-4 text-lg font-semibold underline" href="{{ $pageUrl($page - 1) }}">← Anterior</a>
        @else
            <span class="text-text-secondary">Vamos paso a paso.</span>
        @endif
        @if($page < 5)
            <a class="rounded-lg border-2 border-border px-6 py-4 text-lg font-semibold underline" href="{{ $pageUrl($page + 1) }}">Siguiente →</a>
        @else
            <a class="rounded-lg border-2 border-border px-6 py-4 text-lg font-semibold underline" href="{{ $pageUrl(1) }}">Volver al inicio</a>
            @if($number < count($lessons))
                <a class="rounded-lg border-2 border-border px-6 py-4 text-lg font-semibold underline" href="{{ route('pilot-instruments.editorial-preview', ['lesson' => $number + 1, 'page' => 1]) }}#lesson-page">Siguiente lección →</a>
            @else
                <a class="rounded-lg border border-border px-6 py-4 text-lg underline" href="{{ url('/courses') }}">Volver a cursos</a>
            @endif
        @endif
    </nav>
</section>
<details class="rounded-lg border border-border p-5">
    <summary class="cursor-pointer font-semibold">Guía para el acompañante</summary>
    <div class="mt-4 space-y-4 text-lg leading-relaxed">
        <p>Objetivo: {{ $lesson['objective'] }}</p>
        <p>{{ $lesson['adult'] }}</p>
        <ul class="list-disc pl-6 space-y-3">@foreach($lesson['transfer']['observe'] as $point)<li>{{ $point }}</li>@endforeach</ul>
        <p>No ingresés datos personales. Esta vista no registra observaciones ni acredita dominio.</p>
    </div>
</details>
