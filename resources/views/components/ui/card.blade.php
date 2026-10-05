@props([])
<div {{ $attributes->merge(['class' => 'campus-card bg-surface p-4']) }}>
    {{ $slot }}
</div>
