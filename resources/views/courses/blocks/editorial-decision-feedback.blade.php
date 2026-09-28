@if($answerField ?? false)
    <input type="hidden" name="scenario_answers[{{ $block['id'] }}]" :value="selected?.id ?? ''">
@endif
<div class="mt-4 grid gap-3">
    @foreach($scenario['choices'] as $choice)
        <button type="button" class="passenger-response rounded-lg border-2 border-border p-4 text-left text-lg leading-relaxed" @click="choose(@js($choice['id']))" :disabled="loading" :aria-pressed="selected?.id === @js($choice['id'])" :data-result="selected?.id === @js($choice['id']) ? (selected.correct ? 'correct' : 'incorrect') : null">
            <span>{{ chr(65 + $loop->index) }}. {{ $choice['label'] }}</span>
            <span x-show="selected?.id === @js($choice['id'])" x-cloak class="mt-2 block font-bold" x-text="selected?.correct ? '✓ Respuesta correcta' : '⚠ Respuesta incorrecta'"></span>
        </button>
    @endforeach
</div>
<div x-show="selected" x-cloak role="status" aria-live="polite" aria-atomic="true" class="passenger-response mt-4 rounded-lg border-2 p-5 space-y-3" :data-result="selected ? (selected.correct ? 'correct' : 'incorrect') : null">
    <p class="font-bold text-xl" x-text="selected?.correct ? '✓ ¡Acertaste! Respuesta correcta' : '⚠ Esta respuesta no es correcta. Revisemos el riesgo.'"></p>
    <p class="text-lg leading-relaxed" x-text="selected?.feedback"></p>
    <button type="button" class="rounded border border-current p-3 font-semibold" @click="retry()">Intentar otra decisión</button>
</div>
