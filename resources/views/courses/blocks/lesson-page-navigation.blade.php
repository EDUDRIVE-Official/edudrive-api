<nav aria-label="Páginas de la lección · {{ $navigationPosition === 'top' ? 'inicio' : 'final' }}" class="my-6 flex flex-wrap items-center justify-between gap-4 border-y border-border py-5">
    <button type="button" @click="changeLessonPage(lessonPage - 1)" :disabled="lessonPage === 0" class="min-h-12 rounded-lg border border-border px-5 py-3 text-lg font-semibold disabled:opacity-40">← Anterior</button>
    @if($navigationPosition === 'top')
        <h4 x-ref="pageHeading" tabindex="-1" class="scroll-mt-6 text-lg font-semibold" aria-live="polite" x-text="'Página ' + (lessonPage + 1) + ' de ' + (lastLessonPage + 1)">Página 1 de {{ count($lesson['blocks']) + 2 }}</h4>
    @else
        <span class="text-text-secondary">Podés volver y revisar tus decisiones.</span>
    @endif
    <button type="button" @click="changeLessonPage(lessonPage + 1)" :disabled="lessonPage === lastLessonPage" class="min-h-12 rounded-lg border-2 border-border px-5 py-3 text-lg font-semibold disabled:opacity-40">Siguiente →</button>
</nav>
