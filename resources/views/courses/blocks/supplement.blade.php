@if (! empty($block['payload']['supplement']))
    <details class="rounded-lg border border-border bg-surface p-5">
        <summary class="cursor-pointer text-lg font-semibold">Pistas, explicación y conversación</summary>
        <div data-read-aloud class="lesson-page-copy mt-5 max-w-prose space-y-4 text-lg leading-relaxed">{!! \Illuminate\Support\Str::markdown($block['payload']['supplement'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
    </details>
@endif
