<p class="dc-note">Repaso acompañado · Explicación {{ $reviewIndex + 1 }} de {{ count($lessons) }}</p>
<h1 data-dc-read>{{ $reviewLesson['title'] }}</h1>
<p data-dc-read>Podemos volver a mirar y conversar a nuestro ritmo.</p>
<div class="dc-grid">
    <section class="dc-panel dc-feedback">
        <h2>Lo recordamos juntos</h2>
        <p data-dc-read>{{ $reviewLesson['text'] }}</p>
        <p class="dc-companion" data-dc-read>Podés señalar en el dibujo lo que recordás y pedir ayuda a tu acompañante.</p>
    </section>
    @include('descubro.scene', ['scene' => $reviewLesson['scene']])
</div>
<nav aria-label="Explicaciones para repasar">
    <ol class="dc-list">
        @foreach ($lessons as $index => $item)
            <li>
                @if ($index === $reviewIndex)
                    <strong aria-current="step">{{ $item['title'] }}</strong>
                @else
                    <a href="{{ route('descubro.crossing.show', ['page' => 'review', 'lesson' => $index], false) }}">{{ $item['title'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
<div class="dc-actions">
    @if ($reviewIndex > 0)
        <a class="dc-button" href="{{ route('descubro.crossing.show', ['page' => 'review', 'lesson' => $reviewIndex - 1], false) }}">← Explicación anterior</a>
    @endif
    @if ($reviewIndex < count($lessons) - 1)
        <a class="dc-button" href="{{ route('descubro.crossing.show', ['page' => 'review', 'lesson' => $reviewIndex + 1], false) }}">Siguiente explicación →</a>
    @endif
    <a class="dc-button" href="{{ route('descubro.crossing.show', [], false) }}">Volver a mi recorrido →</a>
</div>
<p class="dc-note">El repaso conserva tu avance. Al volver al recorrido, seguís desde el paso donde quedaste.</p>
