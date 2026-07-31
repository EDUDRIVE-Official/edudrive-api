@props([])
<div class="overflow-x-auto rounded-md border border-border">
    <table {{ $attributes->merge(['class' => 'w-full text-left font-sans text-sm']) }}>
        @isset($head)
            <thead class="bg-background text-text-secondary">
                {{ $head }}
            </thead>
        @endisset
        <tbody class="divide-y divide-border text-text">
            {{ $slot }}
        </tbody>
    </table>
</div>
