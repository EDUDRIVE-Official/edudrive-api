@props([])
<div {{ $attributes->merge(['class' => 'campus-card rounded-md bg-surface p-4 shadow-sm']) }}>
    {{ $slot }}
</div>
